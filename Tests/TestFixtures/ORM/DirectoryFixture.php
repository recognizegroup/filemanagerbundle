<?php
namespace Recognize\FilemanagerBundle\TestFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Recognize\FilemanagerBundle\Entity\Directory;

class DirectoryFixture extends AbstractFixture implements OrderedFixtureInterface {

    /**
     * Creates a virtual directory structure
     *
     * testroot/
     * testroot/test
     * testroot/test/test
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

    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder() {
        return 100;
    }
}