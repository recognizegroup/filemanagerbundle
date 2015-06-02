<?php
namespace Recognize\FilemanagerBundle\Service;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Exception;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Security\DirectoryMaskBuilder;
use Symfony\Component\Security\Acl\Domain\Entry;
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
     * Manages the ACLs for the directories in the database
     *
     * @param MutableAclProviderInterface $aclProvider
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function __construct(MutableAclProviderInterface $aclProvider ) {

        $this->aclProvider = $aclProvider;
    }

    /**
     * Revoke access to a directory for certain roles
     *
     * @param Directory $directory              The directory that should have their rights changed
     * @param $roles                            An array with the roles that should be denied access
     * @param $actions                          The actions that should be denied
     */
    public function denyAccessToDirectory( Directory $directory, $roles, $actions ){
        if( $directory->getId() != 0 || $directory->getId() != null ){
            $objectIdentity = ObjectIdentity::fromDomainObject($directory);

            $this->changeAccessToDirectory( $objectIdentity, $roles, $actions, false );
        } else {
            throw new InvalidArgumentException( "The directory must have an id set to be able to get ACLs" );
        }
    }

    /**
     * Grant access to a directory for certain roles
     *
     * @param Directory $directory              The directory that should have their rights changed
     * @param $roles                            An array with the roles that should be granted access
     * @param $actions                          The actions to allow
     */
    public function grantAccessToDirectory( Directory $directory, $roles, $actions ){
        if( $directory->getId() != 0 || $directory->getId() != null ){
            $objectIdentity = ObjectIdentity::fromDomainObject($directory);

            $this->changeAccessToDirectory( $objectIdentity, $roles, $actions, true );
        } else {
            throw new InvalidArgumentException( "The directory must have an id set to be able to get ACLs" );
        }
    }

    /**
     * Change the access to a directory for certain roles and certain actions
     *
     * @param ObjectIdentity $objectIdentity
     * @param $roles                    An array with the roles that should have their access changed
     * @param $actions                  The actions to change access
     * @param bool $grant_access        Whether to grant access or not
     */
    protected function changeAccessToDirectory( ObjectIdentity $objectIdentity, $roles, $actions, $grant_access ){

        // Make sure the ACL exists
        try {
            $acl = $this->aclProvider->findAcl( $objectIdentity );
        } catch( \Exception $e ){
            $acl = $this->aclProvider->createAcl( $objectIdentity );
        }

        // Generate the class ACEs using the roles as input
        $insert_aces = array();
        for( $i = 0, $length = count( $roles ); $i < $length; $i++ ){
            $role = $roles[$i];
            $roleidentity = new RoleSecurityIdentity( $role );

            $mask = DirectoryMaskBuilder::getMaskFromValues( $actions );

            $found = false;
            $aces = $acl->getObjectAces();
            /** @var Entry $ace */
            foreach( $aces as $index => $ace ){
                if( $ace->getSecurityIdentity()->getRole() == $role ){
                    $updatedbitmask = $ace->getMask();

                    if( $ace->isGranting() === $grant_access ) {
                        $found = true;

                        // Merge the existing mask with the old mask
                        $updatedbitmask |= $mask;
                    } else {

                        // Reverse the bits in the current ACE that exists in the generated mask
                        $updatedbitmask ^= $ace->getMask();
                    }

                    $acl->updateObjectAce($index, $updatedbitmask);
                }
            }

            // Collect the ACEs that should be inserted and insert them outside the update loop
            if( $found == false ){
                $insert_aces[] = array( "identity" => $roleidentity, "mask" => $mask );
            }
        }

        for( $i = 0, $length = count( $insert_aces ); $i < $length; $i++ ){
            $acl->insertObjectAce( $insert_aces[$i]['identity'], $insert_aces[$i]['mask'], 0, $grant_access );
        }

        $this->aclProvider->updateAcl( $acl );
    }


    /**
     * Clear all the access rights for a directory - This is usually done on deletion
     *
     * @param Directory $directory                  The directory to clear the access rights from
     */
    public function clearAccessRightsForDirectory( Directory $directory ){
        if( $directory->getId() != null && $directory->getId() != 0 ){
            $domainIdentity = ObjectIdentity::fromDomainObject( $directory );
            $this->aclProvider->deleteAcl( $domainIdentity );
        }
    }
}
