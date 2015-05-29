<?php
namespace Recognize\FilemanagerBundle\Tests\Entity;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;

class DirectoryTest extends \PHPUnit_Framework_TestCase {

    public function testId(){
        $dir = new Directory();
        $dir->setId( 1 );
        $this->assertEquals(1, $dir->getId() );
    }

    public function testWorkingDirectory(){
        $dir = new Directory();
        $dir->setWorkingDirectory("test");
        $this->assertEquals("test/", $dir->getWorkingDirectory() );
    }

    public function testRelativePath(){
        $dir = new Directory();
        $dir->setWorkingDirectory("test");
        $dir->setRelativePath("test1");
        $this->assertEquals("test1/", $dir->getRelativePath() );
    }

    public function testAbsolutePath(){
        $dir = new Directory();
        $dir->setWorkingDirectory("test");
        $dir->setRelativePath("test1");
        $dir->setDirectoryName("test2");
        $this->assertEquals("test/test1/test2", $dir->getAbsolutePath() );
    }

    public function testParentDirectory(){
        $dir = new Directory();
        $parentdir = new Directory();
        $parentdir->setId( 1 );

        $dir->setParentDirectory( $parentdir );
        $this->assertEquals( $parentdir, $dir->getParentDirectory() );
        $this->assertEquals(1, $dir->getParentid() );
    }

    public function testParentId(){
        $dir = new Directory();
        $dir->setParentId( 1 );
        $this->assertEquals( 1, $dir->getParentid() );
    }


}