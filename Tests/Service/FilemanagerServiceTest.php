<?php
namespace Recognize\FilemanagerBundle\Tests\Service;

use Recognize\FilemanagerBundle\Exception\ConflictException;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Tests\FilesystemTestCase;
use Symfony\Component\Finder\SplFileInfo;

class FilemanagerServiceTest extends FilesystemTestCase {

    public function testCompile(){
        $filemanagerservice = $this->getFilemanagerService();
    }

    /**
     * @expectedException Exception
     */
    public function testSafetyMechanism(){
        return new FilemanagerService( array() );
    }

    protected function getFilemanagerService(){
        return new FilemanagerService( array("default_directory" => $this->workspace) );
    }

    public function testEmptyDirectoryListing(){
        $filemanagerservice = $this->getFilemanagerService();
        $files = $filemanagerservice->getDirectoryContents();

        $this->assertEquals( array(), $files );
    }

    public function testFilledDirectoryListing(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $files = $filemanagerservice->getDirectoryContents();
        $this->assertEquals( $this->getExpectedFilledDirectory(), $files );
    }

    public function testFilledDirectoryListingOneLevelDeep(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $files = $filemanagerservice->getDirectoryContents("testing");
        $this->assertEquals( $this->getExpectedTestingDirectoryContents(), $files );
    }

    public function testNestedFilledDirectory(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $files = $filemanagerservice->getDirectoryContents("", 3);
        $this->assertEquals( $this->getExpectedNestedDirectoryContents(), $files );
    }

    public function testFullFilenameSearching(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $files = $filemanagerservice->searchDirectoryContents("", "/testing2/");
        $this->assertEquals( $this->getExpectedSearchContents(), $files );
    }

    public function testEmptySearching(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $files = $filemanagerservice->searchDirectoryContents("", "/filethatdoesntexist.jpg/");
        $this->assertEquals( array(), $files );
    }


    public function testPartialFilenameAndNestedSearching(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $files = $filemanagerservice->searchDirectoryContents("", "/vel3/");
        $this->assertEquals( $this->getExpectedNestedSearchContents(), $files );
    }

    public function testSearchLimitedToThisDirectory(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $files = $filemanagerservice->searchDirectoryContents("testing", "/level/", true);
        $this->assertEquals( $this->getExpectedLimitedSearchResults(), $files );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testRenamingDirectory(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $filemanagerservice->rename("testing", "testing4");
        $files = $filemanagerservice->searchDirectoryContents( "", "/testing4/" );
        $this->assertEquals($this->getExpectedRenamedDirectory(), $files[0] );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \Recognize\FilemanagerBundle\Exception\ConflictException
     */
    public function testConflictingRenaming(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $filemanagerservice->rename("testing", "testing2");
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \Symfony\Component\Filesystem\Exception\FileNotFoundException
     */
    public function testNonexistingFileRenaming(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $filemanagerservice->rename("testingnonexistingdirectory", "testing2");
    }


    protected function fillTempDirectory(){
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "level2" . DIRECTORY_SEPARATOR . "level3" , 0777, true);
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "testing2" , 0777);
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "testing3" , 0777);
    }

    protected function getExpectedFilledDirectory(){
        $contents = array();
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing3", "", "testing3" );
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing2", "", "testing2" );
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing", "", "testing" );

        return $contents;
    }

    protected function getExpectedTestingDirectoryContents(){
        $contents = array();
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing", "testing/", "level2" );

        return $contents;
    }

    protected function getExpectedSearchContents(){
        $contents = array();
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing2", "", "testing2" );

        return $contents;
    }

    protected function getExpectedLimitedSearchResults(){
        $contents = array();
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "level2/", "testing/", "level2" );

        return $contents;
    }


    protected function getExpectedNestedSearchContents(){
        $contents = array();
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "level2", "testing/level2/", "level3" );

        return $contents;
    }

    protected function getExpectedNestedDirectoryContents(){
        $contents = array();

        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing3", "", "testing3" );
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing2", "", "testing2" );
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing", "", "testing" );
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "level2", "testing/", "level2" );
        $contents[] = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "level2" . DIRECTORY_SEPARATOR . "level3", "testing/level2/", "level3" );

        return $contents;
    }

    protected function getExpectedRenamedDirectory(){
        return new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing4", "", "testing4" );
    }
}