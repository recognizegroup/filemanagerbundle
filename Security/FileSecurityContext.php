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

    private $config;
    private $always_authenticate = false;

    private $security_context;
    private $acl_provider;
    private $authorization_checker = null;

    /**
     * @param array $configuration
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function __construct( array $configuration, AclProviderInterface $aclprovider,
                                 SecurityContextInterface $context, $always_authenticate = false ) {

        $this->config = $configuration;
        $this->always_authenticate = $always_authenticate;
        if( !$always_authenticate ){
            if( isset( $configuration['security'] ) == false ){
                $this->$always_authenticate = true;
            }
        }

        $this->acl_provider = $aclprovider;
        $this->security_context = $context;
    }

    /**
     * Check if an action is granted
     *
     * @param string $action                   The action to check
     * @param string $path                     The directory path to apply the action to
     * @return boolean
     */
    public function isGranted( $action, $path ){
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

                if( strpos( $path, "css" ) !== false ){
                    $parentdirectory = new Directory();
                    $parentdirectory->setId( 1 );
                    $directoryobject->setParentDirectory( $parentdirectory );
                }


                // Make sure to run the directories through the database ACLs first
                try {
                    $domainidentity = $this->getDomainObjectWithACLs( $directoryobject, $securityidentities );
                    return $this->security_context->isGranted( $action, $domainidentity );

                // If no ACLs could be found for the directory, apply the YAML based security
                } catch( \Exception $e ){

                    if( $this->authorization_checker == null ){
                        $this->authorization_checker = new ConfigurationAuthorizationChecker(
                            $this->config['security']['actions'] );
                    }

                    $this->authorization_checker->setCurrentRoles( $roles );
                    return $this->authorization_checker->isGranted( $action );
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
