<?php
namespace Recognize\FilemanagerBundle\Tests\Entity;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;

class FileReferenceTest extends \PHPUnit_Framework_TestCase {


    public function testEmptyFilename(){
        $fileref = new FileReference();
        $this->assertEquals(null, $fileref->getFilename() );
    }

    public function testId(){
        $fileref = new FileReference();
        $fileref->setId( 1 );
        $this->assertEquals(1, $fileref->getId() );
    }

    public function testFilename(){
        $fileref = new FileReference();
        $fileref->setFileName("testname.txt");
        $this->assertEquals("testname.txt", $fileref->getFilename() );
    }

    public function testMimetype(){
        $fileref = new FileReference();
        $fileref->setMimetype("image/png");
        $this->assertEquals("image/png", $fileref->getMimetype() );
    }

    public function testLocale(){
        $fileref = new FileReference();
        $fileref->setLocale("nl");
        $this->assertEquals("nl", $fileref->getLocale() );
    }

    public function testPreviewurl(){
        $fileref = new FileReference();
        $fileref->setPreviewUrl("http://google.nl");
        $this->assertEquals("http://google.nl", $fileref->getPreviewUrl() );
    }

    public function testParentDirectory(){
        $directory = $this->getDirectory();
        $fileref = new FileReference();
        $fileref->setParentDirectory( $directory );
        $this->assertEquals( $directory, $fileref->getParentDirectory() );
        $this->assertEquals("test1/test2/", $fileref->getRelativePath() );
        $fileref->setFileName("testing.txt");
        $this->assertEquals("test/test1/test2/testing.txt", $fileref->getAbsolutePath() );
        $this->assertEquals("test1/test2/", $fileref->getRelativePath() );
    }

    /**
     * @depends testParentDirectory
     */
    public function testAbsolutePathFromDirectory(){
        $directory = $this->getDirectory();
        $fileref = new FileReference();
        $fileref->setParentDirectory( $directory );
        $fileref->setFileName("testing.txt");
        $this->assertEquals("test/test1/test2/testing.txt", $fileref->getAbsolutePath() );
    }

    /**
     * @depends testParentDirectory
     */
    public function testWorkingDirectory(){
        $directory = $this->getDirectory();
        $fileref = new FileReference();
        $fileref->setParentDirectory( $directory );
        $fileref->setFileName("testing.txt");
        $this->assertEquals("test/", $fileref->getWorkingDirectory() );
    }

    /**
     * @depends testParentDirectory
     */
    public function testRelativePath(){
        $directory = $this->getDirectory();
        $fileref = new FileReference();
        $fileref->setParentDirectory( $directory );
        $fileref->setFileName("testing.txt");
        $this->assertEquals("test1/test2/", $fileref->getRelativePath() );
    }


    protected function getDirectory(){
        $directory = new Directory();
        $directory->setId( 1 );
        $directory->setWorkingDirectory("test");
        $directory->setRelativePath("test1");
        $directory->setDirectoryName("test2");

        return $directory;
    }

}