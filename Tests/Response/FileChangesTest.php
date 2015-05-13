<?php
namespace Recognize\FilemanagerBundle\Tests\Response;

use Recognize\FilemanagerBundle\Response\FileChanges;
use Recognize\FilemanagerBundle\Response\FilemanagerResponseBuilder;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Recognize\FilemanagerBundle\Tests\MockFileSecurityContext;
use Symfony\Component\Filesystem\Tests\FilesystemTestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response;

class FileChangesTest extends FilesystemTestCase {

    /** @var FilemanagerService $filemanager */
    private $filemanager;

    public function setUp(){
        parent::setUp();

        $this->filemanager = new FilemanagerService( array("default_directory" => $this->workspace), new MockFileSecurityContext() );

        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "Old directory" , 0777);
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "New directory" , 0777);

        file_put_contents($this->workspace . DIRECTORY_SEPARATOR . 'testing.txt', "TEST CONTENTS");
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR . 'testing2.txt', "TEST CONTENTS");
    }

    public function testRenamingExistingFile(){
        $files = $this->filemanager->searchDirectoryContents("", "/testing/");

        $changes = new FileChanges( "rename", $files[0] );
        $changes->setFileAfterChanges( $files[1] );

        $actualresponse = $this->filterUnreliableFiledata( $changes->toArray() );
        $this->assertEquals( $this->getExpectedRenameChanges(), $actualresponse );
        $this->assertEquals( $changes->getUpdatedFile(), $files[1] );
    }

    public function testRenamingNonexistingDirectory(){
        $olddir = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "Old directory", "", "Old directory" );
        $newdir = new SplFileInfo( $this->workspace . DIRECTORY_SEPARATOR . "New", "", "New" );

        $changes = new FileChanges( "rename", $olddir );
        $changes->setFileAfterChanges( $newdir );

        $actualresponse = $this->filterUnreliableFiledata( $changes->toArray() );
        $this->assertEquals( $this->getExpectedDirectoryRenameChanges(), $actualresponse );
        $this->assertEquals( $changes->getUpdatedFile(), $newdir );
    }


    /**
     * @return array
     */
    protected function getExpectedRenameChanges(){
        $changes = array(
            "type" => "rename",
            "file" => array(
                'type' => 'file',
                'directory' => "",
                'name' => 'testing2.txt',
                'file_extension' => 'txt',
                'path' => 'testing2.txt'
            ),
            "updatedfile" => array(
                'type' => 'file',
                'directory' => "",
                'name' => 'testing.txt',
                'file_extension' => 'txt',
                'path' => 'testing.txt'
            )
        );

        return $changes;
    }

    /**
     * @return array
     */
    protected function getExpectedDirectoryRenameChanges(){
        $changes = array(
            "type" => "rename",
            "file" => array(
                'type' => 'dir',
                'directory' => "",
                'name' => 'Old directory',
                'file_extension' => '',
                'path' => 'Old directory'
            ),
            "updatedfile" => array(
                'type' => 'file',
                'directory' => "",
                'name' => 'New',
                'file_extension' => '',
                'path' => 'New'
            )
        );

        return $changes;
    }

    /**
     * Make sure we filter the time and filesize data to make the tests less unreliable
     *
     * @param array $changes
     * @return mixed
     */
    protected function filterUnreliableFiledata( $changes ){

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