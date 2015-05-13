<?php
namespace Recognize\FilemanagerBundle\Security;

use Recognize\FilemanagerBundle\Entity\Directory;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Checks access for certain actions on directories
 *
 * Class FilePermissionService
 * @package Recognize\FilemanagerBundle\Service
 */
class FileSecurityContext implements FileSecurityContextInterface {

    private $always_authenticate = false;

    private $security_context;
    private $acl_provider;
    private $rolemasks = array();

    /**
     * @param array $configuration
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function __construct( array $configuration, AclProviderInterface $aclprovider,
                                 SecurityContextInterface $context, $always_authenticate = false ) {

        $this->always_authenticate = $always_authenticate;
        if( !$always_authenticate ){
            if( isset( $configuration['security'] ) ){
                $actions = $configuration['security']['actions'];
                $this->yamlActionsToACEs( $actions );

                // If no security is set, grant access to everything
            } else {
                $this->$always_authenticate = true;
            }
        }

        $this->acl_provider = $aclprovider;
        $this->security_context = $context;
    }

    /**
     * Parses an array of actions with the allowed roles into ACEs that are used by the security context
     *
     * @param $actions
     */
    protected function yamlActionsToACEs( $actions ){
        $roles = array();

        // Turn the action: [roles] into role: [actions]
        $action_names = array_keys( $actions );
        for( $i = 0, $length = count($action_names); $i < $length; $i++ ){
            $allowed_action = $action_names[ $i ];

            $allowed_roles = $actions[ $allowed_action ];
            for( $j = 0, $jlength = count( $allowed_roles); $j < $jlength; $j++ ){
                $allowed_role = $allowed_roles[$j];

                if( isset( $roles[ $allowed_role ]) == false ){
                    $roles[ $allowed_role ] = array();
                }

                $roles[ $allowed_role ][] = $allowed_action;
            }
        }

        // Turn the actions into bitmasks
        $role_names = array_keys( $roles );
        for( $i = 0, $length = count( $role_names ); $i < $length; $i++ ){
            $this->rolemasks[ $role_names[$i] ] = $this->getMaskFromValues( $roles[ $role_names[$i] ] );
        }
    }

    /**
     * Turn a list of actions into a bitmask for the ACL system
     *
     * @param $values
     * @return int
     */
    protected function getMaskFromValues( $values ){
        $maskbuilder = new DirectoryMaskBuilder();
        for( $i = 0, $length = count( $values); $i < $length; $i++ ){
            switch( strtolower( $values[$i] ) ){
                case "open":
                    $maskbuilder->add( DirectoryMaskBuilder::OPEN );
                    break;
                case "upload":
                    $maskbuilder->add( DirectoryMaskBuilder::UPLOAD );
                    break;
                case "create":
                    $maskbuilder->add( DirectoryMaskBuilder::CREATE );
                    break;
                case "rename":
                    $maskbuilder->add( DirectoryMaskBuilder::RENAME );
                    break;
                case "move":
                    $maskbuilder->add( DirectoryMaskBuilder::MOVE );
                    break;
                case "delete":
                    $maskbuilder->add( DirectoryMaskBuilder::DELETE );
                    break;
                case "mask_owner":
                    $maskbuilder->add( MaskBuilder::MASK_OWNER );
                    break;
            }
        }

        return $maskbuilder->get();
    }

    /**
     * Check if an action is granted
     *
     * @param string $action                   The action to check
     * @param string $directory                The directory to apply the action to
     * @return boolean
     */
    public function isGranted( $action, $directory ){
        if( $this->always_authenticate == false ){

            $token = $this->security_context->getToken();
            $securityidentities = array();
            if( $token !== null ){

                // Get the security identities of the currently logged in user
                // Get the user first so it gets checked before the roles
                $user = UserSecurityIdentity::fromToken( $token );
                $securityidentities[] = $user;

                // Get the roles
                $roles = $token->getUser()->getRoles();
                for( $i = 0, $length = count( $roles ); $i < $length; $i++ ){
                    $securityidentities[] = new RoleSecurityIdentity( $roles[$i] );
                }

                $directoryobject = new Directory();
                $directoryobject->setId( 22 );

                $parentdirectory = new Directory();
                $parentdirectory->setId( 2 );
                $directoryobject->setParentDirectory( $parentdirectory );


                // Make sure to run the directories through the database ACLs first
                try {
                    $domainidentity = $this->getDomainObjectWithACLs( $directoryobject, $securityidentities );
                    return $this->security_context->isGranted( $action, $domainidentity );

                // If no ACLs could be found for the directory, apply the YAML based security
                } catch( \Exception $e ){

                    $granted = false;
                    $required_mask = $this->getMaskFromValues( array( strtolower( $action ) ) );
                    for( $i = 0, $length = count( $roles ); $i < $length; $i++ ){
                        $role = $roles[$i];
                        if( isset( $this->rolemasks[ $role ] ) && 0 !== ($this->rolemasks[ $role ] & $required_mask) ){
                            $granted = true;
                            break;
                        }

                    }

                    return $granted;
                }

            // Non logged in users DONT get access
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Recursively go up the directory structure until a directory with ACLs
     * for the current security identities is found
     *
     * @param Directory $directory
     * @param SecurityIdentityInterface[] $securityidentities
     * @return ObjectIdentity
     *
     * @throws AclNotFoundException
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    protected function getDomainObjectWithACLs( Directory $directory, $securityidentities ){
       $domainidentity = ObjectIdentity::fromDomainObject( $directory );

        try {
            $this->acl_provider->findAcl( $domainidentity, $securityidentities );

            return $domainidentity;
        } catch( AclNotFoundexception $e ){
            $parent_directory = $directory->getParentDirectory();

            if( $parent_directory !== null ){
                return $this->getDomainObjectWithACLs( $parent_directory, $securityidentities );
            } else {
                throw new AclNotFoundexception();
            }
        }
    }
}
