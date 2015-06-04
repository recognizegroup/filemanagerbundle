<?php
namespace Recognize\FilemanagerBundle\Tests\Functional\Controller;

use Recognize\FilemanagerBundle\Controller\FilemanagerController;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Recognize\FilemanagerBundle\Tests\MockFiledataSynchronizer;
use Recognize\FilemanagerBundle\Tests\MockFileSecurityContext;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Filesystem\Tests\FilesystemTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FilemanagerControllerTest extends FilesystemTestCase {

    public function testReadCorrectResponse(){
        $controller = $this->getController();

        $request = new Request();
        $actualresponse = $controller->read( $request );
        $this->assertEquals( new Response('{"status":"success","data":{"contents":[]}}'), $actualresponse);
    }

    public function testSearchCorrectResponse(){
        $controller = $this->getController();

        $request = new Request();
        $actualresponse = $controller->search( $request );
        $this->assertEquals( new Response('{"status":"success","data":{"contents":[]}}'), $actualresponse);
    }

    public function testPreviewCorrectResponse(){
        $controller = $this->getController();

        $request = new Request();
        $actualresponse = $controller->preview( $request );
        $this->assertEquals( new Response(''), $actualresponse);
    }

    public function testDownloadCorrectResponse(){
        $controller = $this->getController();

        $request = new Request();
        $actualresponse = $controller->download( $request );
        $this->assertEquals( new Response(''), $actualresponse);
    }

    public function testCreateDirectoryCorrectResponse(){
        $controller = $this->getController();

        $request = new Request(array(), array("filemanager_directory" => "", "directory_name" => "test"));

        $actualresponse = $controller->create( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("success", $actualresponseobject['status']);
        $this->assertTrue( count( $actualresponseobject['data']['changes'] ) > 0 );
    }

    public function testInvalidCreateRequest(){
        $controller = $this->getController();

        $request = new Request();

        $actualresponse = $controller->create( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("failed", $actualresponseobject['status']);
    }

    public function testCreateFileCorrectResponse(){
        $controller = $this->getController();

        $this->addTempFile();

        $uploadedfile = new UploadedFile($this->workspace . "/testfile.txt", "testfile2.txt", "text/plain", 0, null, true);
        $request = new Request(array(), array("filemanager_directory" => ""), array(), array(), array("filemanager_upload" => $uploadedfile));

        $actualresponse = $controller->create( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("success", $actualresponseobject['status']);
        $this->assertTrue( count( $actualresponseobject['data']['changes'] ) > 0 );
    }

    public function testMoveFileCorrectResponse(){
        $controller = $this->getController();

        $this->addTempFile();
        $request = new Request(array(), array("filemanager_filepath" => "testfile.txt",
            "filemanager_newdirectory" => "test"
        ));

        $actualresponse = $controller->move( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("success", $actualresponseobject['status']);
        $this->assertTrue( count( $actualresponseobject['data']['changes'] ) > 0 );
    }

    public function testInvalidMoveFileResponse(){
        $controller = $this->getController();

        $this->addTempFile();
        $request = new Request();

        $actualresponse = $controller->move( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("failed", $actualresponseobject['status']);
    }

    public function testRenameFileCorrectResponse(){
        $controller = $this->getController();

        $this->addTempFile();
        $request = new Request(array(), array("filemanager_filename" => "testfile.txt",
            "filemanager_newfilename" => "testfile2.txt",
            "filemanager_directory" => ""
        ));

        $actualresponse = $controller->rename( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("success", $actualresponseobject['status']);
        $this->assertTrue( count( $actualresponseobject['data']['changes'] ) > 0 );
    }

    public function testInvalidRenameFileResponse(){
        $controller = $this->getController();

        $this->addTempFile();
        $request = new Request();

        $actualresponse = $controller->rename( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("failed", $actualresponseobject['status']);
    }

    public function testDeleteFileCorrectResponse(){
        $controller = $this->getController();

        $this->addTempFile();
        $request = new Request(array(), array("filemanager_filename" => "testfile.txt",
            "filemanager_directory" => ""
        ));

        $actualresponse = $controller->delete( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("success", $actualresponseobject['status']);
        $this->assertTrue( count( $actualresponseobject['data']['changes'] ) > 0 );
    }

    public function testInvalidDeleteFileResponse(){
        $controller = $this->getController();

        $this->addTempFile();
        $request = new Request();

        $actualresponse = $controller->delete( $request );
        $actualresponseobject = json_decode( $actualresponse->getContent(), true );
        $this->assertEquals("failed", $actualresponseobject['status']);
    }


    protected function addTempFile(){
        file_put_contents( $this->workspace . "/testfile.txt", "Test contents");
    }

    protected function getController() {
        $container = new Container();
        $filemanager = new FilemanagerService( array("directories" => array( "default" => $this->workspace ) ), new MockFileSecurityContext(), new MockFiledataSynchronizer() );
        $container->set("recognize.file_manager", $filemanager);

        $controller = new FilemanagerController();
        $controller->setContainer( $container );
        return $controller;
    }



}