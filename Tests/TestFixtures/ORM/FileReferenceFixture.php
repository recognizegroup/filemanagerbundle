<?php
namespace Recognize\FilemanagerBundle\TestFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Recognize\FilemanagerBundle\Entity\FileReference;

class FileReferenceFixture extends AbstractFixture implements OrderedFixtureInterface {

    /**
     * Creates two testfiles for fixtures
     *
     * testroot/testfile.txt
     * testroot/test/test/test2file.txt
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager) {
        $dirrepo = $manager->getRepository("Recognize\\FilemanagerBundle\\Entity\\Directory");
        $rootdir = $dirrepo->findOneBy(
            array("working_directory" => "testroot/", "relative_path" => "", "name" => ""));
        $subdir = $dirrepo->findOneBy(
            array("working_directory" => "testroot/", "relative_path" => "test/", "name" => "test"));

        $file = new FileReference();
        $file->setParentDirectory( $rootdir );
        $file->setFileName("testfile.txt");
        $file->setMimetype("text/plain");

        $manager->persist($file);
        $manager->flush();

        $subfile = new FileReference();
        $subfile->setParentDirectory( $subdir );
        $subfile->setFileName("test2file.txt");
        $subfile->setMimetype("text/plain");

        $manager->persist( $subfile );
        $manager->flush();

    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getOrder() {
        return 101;
    }
}