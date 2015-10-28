<?php
namespace Recognize\FilemanagerBundle\Security;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

class NoConnectionAclProvider implements MutableAclProviderInterface {

    /**
     * Retrieves all child object identities from the database.
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $parentOid
     * @param bool $directChildrenOnly
     *
     * @return array returns an array of child 'ObjectIdentity's
     */
    public function findChildren(\Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $parentOid, $directChildrenOnly = false){
        // TODO: Implement findChildren() method.
    }

    /**
     * Returns the ACL that belongs to the given object identity.
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $oid
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface[] $sids
     *
     * @return \Symfony\Component\Security\Acl\Model\AclInterface
     *
     * @throws \Symfony\Component\Security\Acl\Exception\AclNotFoundException when there is no ACL
     */
    public function findAcl(\Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $oid, array $sids = array()) {
        throw new \Symfony\Component\Security\Acl\Exception\AclNotFoundException( "No ACL connection provided");
    }

    /**
     * Returns the ACLs that belong to the given object identities.
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface[] $oids an array of ObjectIdentityInterface implementations
     * @param \Symfony\Component\Security\Acl\Model\SecurityIdentityInterface[] $sids an array of SecurityIdentityInterface implementations
     *
     * @return \SplObjectStorage mapping the passed object identities to ACLs
     *
     * @throws \Symfony\Component\Security\Acl\Exception\AclNotFoundException when we cannot find an ACL for all identities
     */
    public function findAcls(array $oids, array $sids = array())
    {
        throw new \Symfony\Component\Security\Acl\Exception\AclNotFoundException( "No ACL connection provided");
    }

    /**
     * Creates a new ACL for the given object identity.
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $oid
     *
     * @throws \Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException when there already is an ACL for the given
     *                                   object identity
     *
     * @return \Symfony\Component\Security\Acl\Model\MutableAclInterface
     */
    public function createAcl(\Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $oid)
    {
        return new Acl(1, new \Symfony\Component\Security\Acl\Domain\ObjectIdentity('test', 'test'),
            new \Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy(), array(), null);
    }

    /**
     * Deletes the ACL for a given object identity.
     *
     * This will automatically trigger a delete for any child ACLs. If you don't
     * want child ACLs to be deleted, you will have to set their parent ACL to null.
     *
     * @param \Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $oid
     */
    public function deleteAcl(\Symfony\Component\Security\Acl\Model\ObjectIdentityInterface $oid)
    {
        // TODO: Implement deleteAcl() method.
    }

    /**
     * Persists any changes which were made to the ACL, or any associated
     * access control entries.
     *
     * Changes to parent ACLs are not persisted.
     *
     * @param \Symfony\Component\Security\Acl\Model\MutableAclInterface $acl
     */
    public function updateAcl(\Symfony\Component\Security\Acl\Model\MutableAclInterface $acl)
    {
        // TODO: Implement updateAcl() method.
    }
}