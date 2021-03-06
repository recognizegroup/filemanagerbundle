<?php
namespace Recognize\FilemanagerBundle\Tests\Service;

use Recognize\FilemanagerBundle\Exception\ConflictException;
use Recognize\FilemanagerBundle\Exception\FileTooLargeException;
use Recognize\FilemanagerBundle\Exception\UploadException;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Recognize\FilemanagerBundle\Tests\TestFixtures\TestPNG;
use Recognize\FilemanagerBundle\Tests\MockFiledataSynchronizer;
use Recognize\FilemanagerBundle\Tests\MockFileSecurityContext;
use Symfony\Component\Filesystem\Tests\FilesystemTestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class FilemanagerServiceTest extends FilesystemTestCase {

    public function testCompile(){
        $filemanagerservice = $this->getFilemanagerService();
    }

    /**
     * @expectedException Exception
     */
    public function testSafetyMechanism(){
        return new FilemanagerService( array(), new MockFileSecurityContext(), new MockFiledataSynchronizer() );
    }

    protected function getFilemanagerService(){
        return new FilemanagerService( array("directories" => array( "default" => $this->workspace) ), new MockFileSecurityContext(), new MockFiledataSynchronizer() );
    }

    public function testGetWorkingDirectory(){
        $filemanagerservice = new FilemanagerService( array("directories" => array( "default" => "abc") ), new MockFileSecurityContext(), new MockFiledataSynchronizer() );
        $this->assertEquals( "abc", $filemanagerservice->getWorkingDirectory() );
    }

    public function testSetWorkingDirectory(){
        $filemanagerservice = new FilemanagerService( array("directories" => array( "default" => $this->workspace,
            "user_directory" => "users" ) ), new MockFileSecurityContext(), new MockFiledataSynchronizer() );
        $filemanagerservice->setWorkingDirectory( "user_directory" );
        $this->assertEquals( "users", $filemanagerservice->getWorkingDirectory() );
    }

    /**
     * @expectedException Exception
     */
    public function testSetFaultyWorkingDirectory(){
        $filemanagerservice = new FilemanagerService( array("directories" => array( "default" => $this->workspace,
            "user_directory" => $this->workspace . "/users" ) ), new MockFileSecurityContext(), new MockFiledataSynchronizer() );
        $filemanagerservice->setWorkingDirectory( "user_directory" );
        $this->assertEquals( "users", $filemanagerservice->getWorkingDirectory() );
    }


    /**
     * @expectedException Exception
     */
    public function testSetInvalidWorkingDirectory(){
        $filemanagerservice = new FilemanagerService( array("directories" => array( "default" => $this->workspace,
            "user_directory" => "users" ) ), new MockFileSecurityContext(), new MockFiledataSynchronizer() );
        $filemanagerservice->setWorkingDirectory( "asdfasdf" );
        $this->assertEquals( "users", $filemanagerservice->getWorkingDirectory() );
    }


    public function testWorkspace(){
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

    /**
     * @expectedException \RunTimeException
     */
    public function testDisallowedDotDirectoriesOnRead(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $filemanagerservice->getDirectoryContents("testing/../", "testing2");
    }

    /**
     * @expectedException \RunTimeException
     */
    public function testDisallowedDotDirectoriesOnSearch(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $filemanagerservice->searchDirectoryContents("testing/../", "testing2");
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

        $changes = $filemanagerservice->rename("testing", "testing4");
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


    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testCreateDirectory(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->createDirectory("New directory");

        $this->assertEquals( $this->getExpectedCreatedDirectory(), $changes->getFile() );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \RuntimeException
     */
    public function testCreateDottedDirectory(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->createDirectory("..");
        $this->assertEquals( $this->getExpectedCreatedDirectory(), $changes->getFile() );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \Recognize\FilemanagerBundle\Exception\ConflictException
     */
    public function testConflictingDirectoryCreation(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->createDirectory("testing");
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \RuntimeException
     */
    public function testUnexpectedErrorDirectoryCreation(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR . 'testing.txt', "TEST CONTENTS");


        $changes = $filemanagerservice->createDirectory("testing.txt/derp");
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testUploadFile(){
        $filemanagerservice = $this->getFilemanagerService();

        $tempfilepath = $this->workspace . DIRECTORY_SEPARATOR . 'temporaryfile.txt';
        $this->fillTempDirectory();
        file_put_contents( $tempfilepath, "TEST CONTENTS");
        $tempfile = new UploadedFile( $tempfilepath, "temporaryfile", "text/plain", filesize( $tempfilepath ), null, true );

        $changes = $filemanagerservice->saveUploadedFile( $tempfile, "testfile.txt" );
        $this->assertEquals( $this->getExpectedUploadedFile(), $changes->getFile() );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testUploadToTestingDirectory(){
        $filemanagerservice = $this->getFilemanagerService();

        $tempfilepath = $this->workspace . DIRECTORY_SEPARATOR . 'temporaryfile.txt';
        $this->fillTempDirectory();
        file_put_contents( $tempfilepath, "TEST CONTENTS");
        $tempfile = new UploadedFile( $tempfilepath, "temporaryfile", "text/plain", filesize( $tempfilepath ), null, true );

        $filemanagerservice->goToDeeperDirectory("testing");
        $changes = $filemanagerservice->saveUploadedFile( $tempfile, "testfile.txt" );
        $this->assertEquals( $this->getExpectedUploadedFileInTesting(), $changes->getFile() );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \Recognize\FilemanagerBundle\Exception\FileTooLargeException
     */
    public function testUploadThatIsTooLarge(){
        $filemanagerservice = $this->getFilemanagerService();

        $tempfilepath = $this->workspace . DIRECTORY_SEPARATOR . 'temporaryfile.txt';
        $this->fillTempDirectory();
        file_put_contents( $tempfilepath, "TEST CONTENTS");
        $tempfile = new UploadedFile( $tempfilepath, "temporaryfile", "text/plain", filesize( $tempfilepath ), UPLOAD_ERR_INI_SIZE, true );
        $changes = $filemanagerservice->saveUploadedFile( $tempfile, "temporaryfile2.txt" );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \Recognize\FilemanagerBundle\Exception\UploadException
     */
    public function testUploadThatHasAnError(){
        $filemanagerservice = $this->getFilemanagerService();

        $tempfilepath = $this->workspace . DIRECTORY_SEPARATOR . 'temporaryfile.txt';
        $this->fillTempDirectory();
        file_put_contents( $tempfilepath, "TEST CONTENTS");
        $tempfile = new UploadedFile( $tempfilepath, "temporaryfile", "text/plain", filesize( $tempfilepath ), 1234, true );
        $changes = $filemanagerservice->saveUploadedFile( $tempfile, "temporaryfile2.txt" );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testUploadWithCopy(){
        $filemanagerservice = $this->getFilemanagerService();

        $tempfilepath = $this->workspace . DIRECTORY_SEPARATOR . 'temporaryfile.txt';
        $this->fillTempDirectory();
        file_put_contents( $tempfilepath, "TEST CONTENTS");
        $tempfile = new UploadedFile( $tempfilepath, "temporaryfile", "text/plain", filesize( $tempfilepath ), null, true );

        $changes = $filemanagerservice->saveUploadedFile( $tempfile, "temporaryfile.txt", true );
        $this->assertEquals( $this->getExpectedUploadedFileWithCopy(), $changes->getFile() );
    }


    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testMovingDirectoryDeeper(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->move("testing3", "testing12");
        $files = $filemanagerservice->searchDirectoryContents( "", "/testing3/" );
        $this->assertEquals($this->getExpectedMovedDownDirectory(), $files[0] );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testMovingDirectoryHigher(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->move("testing/level2/level3", "testing");
        $files = $filemanagerservice->searchDirectoryContents( "testing", "/level3/" );
        $this->assertEquals($this->getExpectedMovedUpDirectory(), $files[0] );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \RuntimeException
     */
    public function testMovingToExistingDirectory(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->move("testing3", "");
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \RuntimeException
     */
    public function testMovingNonexistingDirectory(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->move("notafile", "");
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testDeletingFile(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR . 'testing.txt', "TEST CONTENTS");

        $changes = $filemanagerservice->delete("testing.txt" );
        $this->assertEquals( $this->getExpectedDeletedFile(), $this->filterUnreliableFiledataFromChanges( $changes->toArray() ) );
        $this->assertTrue( count( $filemanagerservice->searchDirectoryContents("", "/testing.txt/") ) == 0 );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     * @expectedException \RuntimeException
     */
    public function testDeletingNonexistingFile(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $changes = $filemanagerservice->delete("thisfiledoesnotexist");
    }


    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testDeletingDirectoryWithFiles(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR .  'testing/testing.txt', "TEST CONTENTS");

        $changes = $filemanagerservice->delete("testing" );
        $this->assertEquals( $this->getExpectedDeletedDirectory(), $this->filterUnreliableFiledataFromChanges( $changes->toArray() ) );
        $this->assertTrue( count( $filemanagerservice->searchDirectoryContents("", "/testing.txt/") ) == 0 );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testLivePreview(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        $testimage = new TestPNG();
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR .  'testing/testing.png', $testimage->getContents() );

        $response = $filemanagerservice->getLiveFilePreview("testing/testing.png");
        $this->assertEquals( $testimage->getContents(), $response->getContent() );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testEmptyLivePreview(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        file_put_contents($this->workspace . DIRECTORY_SEPARATOR .  'testing/testing.txt', "Test contents" );

        $response = $filemanagerservice->getLiveFilePreview("testing/testing.txt");
        $this->assertEquals( "", $response->getContent() );
    }

    /**
     * @depends testPartialFilenameAndNestedSearching
     */
    public function testDownloadFile(){
        $filemanagerservice = $this->getFilemanagerService();
        $this->fillTempDirectory();

        file_put_contents($this->workspace . DIRECTORY_SEPARATOR .  'testing/testing.txt', "Test contents" );

        $response = $filemanagerservice->downloadFile("testing/testing.txt");
        $this->assertEquals( "Test contents", $response->getContent() );
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

    protected function getExpectedCreatedDirectory(){
        return new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "New directory", "", "New directory" );
    }

    protected function getExpectedUploadedFile(){
        return new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testfile.txt", "", "testfile.txt" );
    }

    protected function getExpectedUploadedFileWithCopy(){
        return new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "temporaryfile(2).txt", "", "temporaryfile(2).txt" );
    }

    protected function getExpectedUploadedFileInTesting(){
        return new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing/testfile.txt", "testing/", "testfile.txt" );
    }

    protected function getExpectedMovedDownDirectory(){
        return new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing12/testing3", "testing12/", "testing3" );
    }

    protected function getExpectedMovedUpDirectory(){
        return new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "testing/level3", "testing/", "level3" );
    }

    protected function getExpectedDeletedFile(){
        return array("type" => "delete", "file" => array("file_extension" => "txt",
            'mimetype' => "text/plain",
            "name" => "testing.txt", "path" => "testing.txt", "directory" => "", "type" => "file") );
    }

    protected function getExpectedDeletedDirectory(){
        return array("type" => "delete", "file" => array("file_extension" => "",
            'mimetype' => "directory",
            "name" => "testing", "path" => "testing", "directory" => "", "type" => "dir") );
    }

    /**
     * Make sure we filter the time and filesize data to make the tests less unreliable
     *
     * @param array $changes
     * @return mixed
     */
    protected function filterUnreliableFiledataFromChanges( $changes ){

        if( isset( $changes['file'] ) ){
            if( isset( $changes['file']['size'] ) ){
                unset( $changes['file']['size'] );
            }

            if( isset( $changes['file']['date_modified'] ) ){
                unset( $changes['file']['date_modified'] );
            }
        }

        if( isset( $changes['updatedfile'] ) ){
            if( isset( $changes['updatedfile']['size'] ) ){
                unset( $changes['updatedfile']['size'] );
            }

            if( isset( $changes['updatedfile']['date_modified'] ) ){
                unset( $changes['updatedfile']['date_modified'] );
            }
        }

        return $changes;
    }

}