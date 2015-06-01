<?php
namespace Recognize\FilemanagerBundle\Tests\Functional\Repository;

use Doctrine\ORM\EntityManager;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Repository\DirectoryRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DirectoryRepositoryTest extends KernelTestCase {

    /**
     * @var DirectoryRepository
     */
    protected $repository;

    public function setUp(){
        self::bootKernel();
        $container = static::$kernel->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine')->getManager();
        $this->repository = $em->getRepository("RecognizeFilemanagerBundle:Directory");
    }

    public function testGetEmptyDirectory(){
        $newdir = $this->repository->getEmptyDirectory("test", "test", "test" );
        $this->assertEquals( $this->getExpectedEmptyDirectory(), $newdir );
    }

    public function testGetDirectory(){
        $existingdir = $this->repository->findDirectoryByLocation("testroot", "test", "test" );

        $this->assertEquals( $this->getExpectedGetDirectoryLocation(), $existingdir[0] );
    }

    public function testGetParentDirectory(){
        $existingdir = $this->repository->findParentDirectory("testroot/", "test" );

        $this->assertEquals( $this->getExpectedParentDirectory(), $existingdir );
    }

    public function testFindRootChildrenByLocation(){
        $existingdirs = $this->repository->findDirectoryChildrenByLocation("testroot", "", "" );

        $this->assertEquals( $this->getExpectedRootChildren(), $existingdirs );
    }

    public function testFindDirectoryChildren(){
        $existingdirs = $this->repository->findDirectoryChildrenByLocation("testroot/", "test", "" );

        $this->assertEquals( $this->getExpectedDirectoryChildren(), $existingdirs );
    }


    public function testFindEmptyDirectoryChildren(){
        $existingdirs = $this->repository->findDirectoryChildrenByLocation("testroot/", "test/", "test" );

        $this->assertEquals( array(), $existingdirs );
    }


    protected function getExpectedGetDirectoryLocation(){
        $dir = new Directory();
        $dir->setWorkingDirectory("testroot");
        $dir->setRelativePath("test");
        $dir->setDirectoryName("test");
        $dir->setId( 3 );
        $dir->setParentId( 2 );

        return $dir;
    }

    protected function getExpectedParentDirectory(){
        $dir = new Directory();
        $dir->setWorkingDirectory("testroot");
        $dir->setRelativePath("");
        $dir->setDirectoryName("test");
        $dir->setId( 2 );
        $dir->setParentId( 1 );

        return $dir;
    }

    protected function getExpectedRootChildren(){
        $dirs = array();

        $dir = new Directory();
        $dir->setWorkingDirectory("testroot");
        $dir->setRelativePath("");
        $dir->setDirectoryName("test");
        $dir->setId( 2 );
        $dir->setParentId( 1 );

        $dirs[] = $dir;

        $dir = new Directory();
        $dir->setWorkingDirectory("testroot");
        $dir->setRelativePath("test");
        $dir->setDirectoryName("test");
        $dir->setId( 3 );
        $dir->setParentId( 2 );

        $dirs[] = $dir;
        return $dirs;
    }

    protected function getExpectedDirectoryChildren(){
        $dirs = array();

        $dir = new Directory();
        $dir->setWorkingDirectory("testroot");
        $dir->setRelativePath("test");
        $dir->setDirectoryName("test");
        $dir->setId( 3 );
        $dir->setParentId( 2 );

        $dirs[] = $dir;
        return $dirs;
    }

    protected function getExpectedEmptyDirectory(){
        $dir = new Directory();
        $dir->setWorkingDirectory("test");
        $dir->setRelativePath("test");
        $dir->setDirectoryName("test");

        return $dir;
    }
}