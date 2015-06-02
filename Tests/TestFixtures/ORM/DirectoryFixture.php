<?php
namespace Recognize\FilemanagerBundle\TestFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Security\DirectoryMaskBuilder;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

class DirectoryFixture extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface {

    protected $container;

    /**
     * Creates a virtual directory structure
     *
     * testroot/
     * testroot/test
     * testroot/test/test
     * testroot/test/test/subtest
     * aclroot/
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager) {
        $root = new Directory();
        $root->setWorkingDirectory("testroot");
        $root->setRelativePath("");
        $root->setDirectoryName("");

        $manager->persist( $root );
        $manager->flush();

        $dir = new Directory();
        $dir->setParentDirectory( $root );
        $dir->setWorkingDirectory("testroot");
        $dir->setRelativePath("");
        $dir->setDirectoryName("test");

        $manager->persist( $dir );
        $manager->flush();

        $subdir = new Directory();
        $subdir->setParentDirectory( $dir );
        $subdir->setWorkingDirectory("testroot");
        $subdir->setRelativePath("test");
        $subdir->setDirectoryName("test");

        $manager->persist( $subdir );
        $manager->flush();

        $subsubdir = new Directory();
        $subsubdir->setParentDirectory( $dir );
        $subsubdir->setWorkingDirectory("testroot");
        $subsubdir->setRelativePath("test/test");
        $subsubdir->setDirectoryName("subtest");

        $manager->persist( $subsubdir );
        $manager->flush();

        $acldir = new Directory();
        $acldir->setParentDirectory( $dir );
        $acldir->setWorkingDirectory("aclroot");
        $acldir->setRelativePath("");
        $acldir->setDirectoryName("");

        $manager->persist( $acldir );
        $manager->flush();


        $this->loadAcls( $root, $dir, $subdir );
    }

    /**
     * Load the ACLs for the directories
     *
     * Creates a structure like this
     *
     * Directories       | User open | Admin open | User delete
     * ------------------|-----------|------------|------------
     * root              |allowed    |allowed     |disallowed
     * root/dir          |allowed    |disallowed  |disallowed
     * root/dir/subdir   |disallowed |allowed     |allowed
     * root/dir/subdir/..|empty      |empty       |empty
     *
     *
     * @param Directory $root
     * @param Directory $dir
     * @param Directory $subdir
     */
    protected function loadAcls( Directory $root, Directory $dir, Directory $subdir ){
        /** @var MutableAclProviderInterface $aclprovider */
        $aclprovider = $this->container->get('security.acl.provider');

        $roleAdminIdentity = new RoleSecurityIdentity("ROLE_TESTADMIN");
        $roleUserIdentity = new RoleSecurityIdentity("ROLE_TESTUSER");

        $maskbuilder = new DirectoryMaskBuilder();
        $openmask = $maskbuilder->getMaskFromValues(array('open'));
        $deletemask = $maskbuilder->getMaskFromValues(array('delete'));

        $rootIdentity = ObjectIdentity::fromDomainObject( $root );
        $dirIdentity = ObjectIdentity::fromDomainObject( $dir );
        $subdirIdentity = ObjectIdentity::fromDomainObject( $subdir );

        // Create the ACLS
        $rootacl = $aclprovider->createAcl( $rootIdentity );
        $diracl = $aclprovider->createAcl( $dirIdentity );
        $subdiracl = $aclprovider->createAcl( $subdirIdentity );

        $rootacl->insertObjectAce($roleAdminIdentity, $openmask);
        $rootacl->insertObjectAce($roleUserIdentity, $openmask);
        $rootacl->insertObjectAce($roleUserIdentity, $deletemask, 0, false );

        $aclprovider->updateAcl( $rootacl );

        $diracl->insertObjectAce($roleAdminIdentity, $openmask);
        $diracl->insertObjectAce($roleUserIdentity, $openmask, 0, false );
        $diracl->insertObjectAce($roleUserIdentity, $deletemask, 0, false );

        $aclprovider->updateAcl( $diracl );

        $subdiracl->insertObjectAce($roleAdminIdentity, $openmask, 0, false);
        $subdiracl->insertObjectAce($roleUserIdentity, $openmask );
        $subdiracl->insertObjectAce($roleUserIdentity, $deletemask );

        $aclprovider->updateAcl( $subdiracl );
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder() {
        return 100;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null){
        $this->container = $container;
    }
}