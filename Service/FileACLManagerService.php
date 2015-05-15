<?php
namespace Recognize\FilemanagerBundle\Service;

use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Exception;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Security\DirectoryMaskBuilder;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;

/**
 * Creates and updates database ACLs for the directories in the filemanager
 *
 * Class FileACLManagerService
 * @package Recognize\FilemanagerBundle\Service
 */
class FileACLManagerService {

    private $working_directory;

    /**
     * @var MutableAclProviderInterface
     */
    protected $aclProvider;

    /**
     * @var UserSecurityIdentity[]
     */
    protected $securityIdentities;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var SecurityContextInterface
     */
    protected $securitycontext;

    protected $basedirectory;

    /**
     *
     *
     * @param MutableAclProviderInterface $aclProvider
     * @param TokenStorageInterface $tokenStorage
     * @param EntityManager $em
     * @param array $configuration
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function __construct(MutableAclProviderInterface $aclProvider, TokenStorageInterface $tokenStorage,
                                EntityManager $em, SecurityContextInterface $context, array $configuration) {

        $this->securitycontext = $context;
        $this->aclProvider = $aclProvider;


        // Retrieve the security identities of the user
        $user = $tokenStorage->getToken()->getUser();
        $securityidentities = array();

        $securityidentities[] = UserSecurityIdentity::fromAccount( $user );
        $roles = $user->getRoles();
        for( $i = 0, $length = count( $roles ); $i < $length; $i++ ){
            $securityidentities[] = new RoleSecurityIdentity( $roles[$i] );
        }
        $this->securityIdentities = $securityidentities;


        $this->basedirectory = new ObjectIdentity('class', 'Recognize\\FilemanagerBundle\\Entity\\Directory');
        if (isset($configuration['default_directory'])) {
            $this->working_directory = $configuration['default_directory'];
        } else {
            throw new \RuntimeException("Default upload and file management directory should be set! ");
        }
    }

    /**
     * Set the base permissions of every managed directory
     *
     * @param array $roles          An array with the roles as keys and the accepted actions as the values
     *                              Possible actions are VIEW, CREATE, DELETE, EDIT and MASK_OWNER
     */
    public function setBasePermissions( $roles ){
        $securityidentity = $this->basedirectory;

        // Make sure the base ACL exists
        try {
            $acl = $this->aclProvider->findAcl( $securityidentity );
        } catch( \Exception $e ){
            $acl = $this->aclProvider->createAcl( $securityidentity );
        }

        // Generate the class ACEs using the roles as input
        $insert_aces = array();

        $rolekeys = array_keys( $roles );
        for( $i = 0, $length = count( $rolekeys ); $i < $length; $i++ ){
            $role = $rolekeys[$i];
            $roleidentity = new RoleSecurityIdentity( $role );

            $values = $roles[ $rolekeys[ $i ] ];
            $mask = DirectoryMaskBuilder::getMaskFromValues( $values );

            $found = false;
            $classaces = $acl->getClassAces();
            foreach( $classaces as $index => $classace ){
                if( $classace->getSecurityIdentity()->getRole() == $role ){
                    $found = true;

                    $acl->updateClassAce( $index, $mask );
                    break;
                }
            }

            // Collect the ACEs that should be inserted and insert them outside the update loop
            if( $found == false ){
                $insert_aces[] = array( "identity" => $roleidentity, "mask" => $mask );
            }
        }

        for( $i = 0, $length = count( $insert_aces ); $i < $length; $i++ ){
            $acl->insertClassAce( $insert_aces[$i]['identity'], $insert_aces[$i]['mask'] );
        }

        $this->aclProvider->updateAcl( $acl );
    }

    /**
     * Revoke access to a directory for certain roles
     *
     * @param $directory
     * @param $roles                An array with the roles as keys and the accepted actions as the values
     *                              Possible actions are VIEW, CREATE, DELETE, EDIT and MASK_OWNER
     */
    public function denyAccessToDirectory( $directory, $roles ){
        $objectIdentity = ObjectIdentity::fromDomainObject($directory);

        // Make sure the base ACL exists
        try {
            $acl = $this->aclProvider->findAcl( $objectIdentity );
        } catch( \Exception $e ){
            $acl = $this->aclProvider->createAcl( $objectIdentity );
        }

        // Generate the class ACEs using the roles as input
        $insert_aces = array();
        $rolekeys = array_keys( $roles );
        for( $i = 0, $length = count( $rolekeys ); $i < $length; $i++ ){
            $role = $rolekeys[$i];
            $roleidentity = new RoleSecurityIdentity( $role );

            $values = $roles[ $rolekeys[ $i ] ];
            $mask = $this->getMaskFromValues( $values );

            $found = false;
            $aces = $acl->getObjectAces();
            foreach( $aces as $index => $ace ){
                if( $ace->getSecurityIdentity()->getRole() == $role && $ace->isGranted() == false ){
                    $found = true;

                    $acl->updateObjectAce( $index, $mask );
                    break;
                }
            }

            // Collect the ACEs that should be inserted and insert them outside the update loop
            if( $found == false ){
                $insert_aces[] = array( "identity" => $roleidentity, "mask" => $mask );
            }
        }

        for( $i = 0, $length = count( $insert_aces ); $i < $length; $i++ ){
            $acl->insertObjectAce( $insert_aces[$i]['identity'], $insert_aces[$i]['mask'], 0, false );
        }

        $this->aclProvider->updateAcl( $acl );
    }

    public function testAcl(){
        /*$roles = array(
            'ROLE_USER' => array('VIEW', 'CREATE' ),
            'ROLE_ADMIN' => array('VIEW', 'CREATE', 'EDIT', 'DELETE'),
            'ROLE_SUPER_ADMIN' => array('VIEW', 'CREATE', 'EDIT', 'DELETE', 'MASK_OWNER')
        );

        $this->setBasePermissions( $roles );*/

        $directory = new Directory();
        $directory->setId( 1 );

        $this->denyAccessToDirectory( $directory, array('ROLE_USER' => array('OPEN') ) );

        //var_dump( $this->canReadDirectory( $directory ) );
    }

}
