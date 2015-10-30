<?php
namespace Recognize\FilemanagerBundle\Service;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Doctrine\DBAL\ConnectionException;
use Doctrine\ORM\EntityManager;
use Exception;
use FOS\UserBundle\Model\GroupInterface;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Security\DirectoryMaskBuilder;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Util\ClassUtils;

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
     * @param Directory $directory                    The directory that should have their rights changed
     * @param array $identities                       An array with either roles, users or groups that should be denied access
     * @param array $actions                          The actions to allow
     */
    public function denyAccessToDirectory( Directory $directory, $identities, $actions ){
        if( $directory->getId() != 0 || $directory->getId() != null ){
            $objectIdentity = ObjectIdentity::fromDomainObject($directory);

            if( is_array( $identities ) == false ){
                $identities = array( $identities );
            }

            $securityidentities = $this->getMixedSecurityIdentities( $identities );
            $this->changeAccessToDirectory( $objectIdentity, $securityidentities, $actions, false );
        } else {
            throw new InvalidArgumentException( "The directory must have an id set to be able to get ACLs" );
        }
    }

    /**
     * Grant access to a directory for certain roles
     *
     * @param Directory $directory                    The directory that should have their rights changed
     * @param mixed $identities                       An array with either roles, users or groups that should be granted access
     * @param array $actions                          The actions to allow
     */
    public function grantAccessToDirectory( Directory $directory, $identities, $actions ){
        if( $directory->getId() != 0 || $directory->getId() != null ){
            $objectIdentity = ObjectIdentity::fromDomainObject($directory);

            if( is_array( $identities ) == false ){
                $identities = array( $identities );
            }

            $securityidentities = $this->getMixedSecurityIdentities( $identities );
            $this->changeAccessToDirectory( $objectIdentity, $securityidentities, $actions, true );
        } else {
            throw new InvalidArgumentException( "The directory must have an id set to be able to get ACLs" );
        }
    }

    /**
     * Clear all the acl entries for a specific directory in combination with the security identities given
     *
     * @param Directory $directory                      The directory that should have their rights changed
     * @param mixed $identities                         An array with either roles, users or groups that should have their permissions cleared
     */
    public function clearAccessToDirectory( Directory $directory, $identities ){
        if( $directory->getId() != 0 || $directory->getId() != null ){
            $objectIdentity = ObjectIdentity::fromDomainObject($directory);

            if( is_array( $identities ) == false ){
                $identities = array( $identities );
            }

            $securityidentities = $this->getMixedSecurityIdentities( $identities );
            try {
                /** @var Acl $acl */
                $acl = $this->aclProvider->findAcl( $objectIdentity );

                foreach( $securityidentities as $identity ){

                    /** @var Entry[] $aces */
                    $aces = $acl->getObjectAces();
                    foreach( $aces as $index => $ace ){
                        if( $ace->getSecurityIdentity()->equals( $identity ) ){
                            $acl->deleteObjectAce( $index );
                        }
                    }
                }

                $this->aclProvider->updateAcl( $acl );
            } catch( \Exception $e ){
                // If no ACLs are found - Just do nothing
            }
        } else {
            throw new InvalidArgumentException( "The directory must have an id set to be able to get ACLs" );
        }

    }

    /**
     * Change the access to a directory for certain roles and certain actions
     *
     * @param ObjectIdentity $objectIdentity
     * @param mixed $securityidentities         An array with security identity objects that should have their access changed
     * @param array $actions                    The actions to change access
     * @param bool $grant_access                Whether to grant access or not
     */
    protected function changeAccessToDirectory( ObjectIdentity $objectIdentity, $securityidentities, $actions, $grant_access ){

        // Make sure the ACL exists
        try {
            $acl = $this->aclProvider->findAcl( $objectIdentity );
        } catch( \Exception $e ){
            $acl = $this->aclProvider->createAcl( $objectIdentity );
        }

        // Generate the class ACEs using the securityidentities as input
        $insert_aces = array();
        for( $i = 0, $length = count( $securityidentities ); $i < $length; $i++ ){
            $identity = $securityidentities[ $i ];

            $mask = DirectoryMaskBuilder::getMaskFromValues( $actions );

            $found = false;
            $aces = $acl->getObjectAces();
            /** @var Entry $ace */
            foreach( $aces as $index => $ace ){
                if( $ace->getSecurityIdentity()->equals( $identity ) ){
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
                $insert_aces[] = array( "identity" => $identity, "mask" => $mask );
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

    /**
     * Get a list of secu
     *
     * @param array $identities
     */
    private function getMixedSecurityIdentities(array $identities){
        $securityIdentities = array();
        for( $i = 0, $length = count( $identities); $i < $length; $i++ ) {
            $identity = $identities[$i];

            if( is_string( $identity ) || $identity instanceof RoleInterface ){
                $securityIdentities[] = new RoleSecurityIdentity( $identity );
            } elseif( $identity instanceof UserInterface ){
                $securityIdentities[] = UserSecurityIdentity::fromAccount( $identity );
            } elseif ( $identity instanceof GroupInterface ){

                // Because the Symfony2 ACL service requires a ton of rewiring if we want to add
                // User groups, simply abuse the user security identity for our purposes
                $securityIdentities[] = new UserSecurityIdentity( $identity->getId(), ClassUtils::getRealClass( $identity ) );
            }
        }

        return $securityIdentities;
    }
}
