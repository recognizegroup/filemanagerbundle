<?php
namespace Recognize\FilemanagerBundle\Tests\Functional\Repository;

use Doctrine\ORM\EntityManager;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Repository\DirectoryRepository;
use Recognize\FilemanagerBundle\Repository\FileRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FileRepositoryTest extends KernelTestCase {

    /**
     * @var FileRepository
     */
    protected $repository;

    protected $rootdir;

    public function setUp(){
        self::bootKernel();
        $container = static::$kernel->getContainer();

        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->repository = $em->getRepository("RecognizeFilemanagerBundle:FileReference");

        /** @var DirectoryRepository $dirrepository */
        $dirrepository = $em->getRepository("RecognizeFilemanagerBundle:Directory");
        $directories = $dirrepository->findDirectoryByLocation("testroot", "", "");
        $this->rootdir = $directories[ 0 ];
    }

    public function testGetFilesInDirectory(){
        $files = $this->repository->getFilesInDirectory( $this->rootdir );
        $this->assertEquals( $this->getExpectedRootFiles(), $files );
    }

    public function testGetFile(){
        $file = $this->repository->getFile( $this->rootdir, "testfile.txt" );
        $this->assertEquals( $this->getExpectedFile(), $file );
    }

    public function testReferencesExistBelowExistingPath(){
        $this->assertTrue( $this->repository->referencesExistBelowPath( "testroot/", "" ) );
    }

    public function testReferencesExistBelowNonexistingPath(){
        $this->assertFalse( $this->repository->referencesExistBelowPath( "testroot/", "abcdefg/" ) );
    }


    protected function getExpectedRootFiles(){
        $file = new FileReference();
        $file->setId( 1 );
        $file->setParentDirectory( $this->rootdir );
        $file->setMimetype("text/plain");
        $file->setFileName("testfile.txt");
        $file->setDirectoryObject( null );

        $files = array();
        $files[] = $file;

        return $files;
    }

    protected function getExpectedFile(){
        $file = new FileReference();
        $file->setId( 1 );
        $file->setParentDirectory( $this->rootdir );
        $file->setMimetype("text/plain");
        $file->setFileName("testfile.txt");
        $file->setDirectoryObject( null );

        return $file;
    }

}