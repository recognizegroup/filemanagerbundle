<?php
namespace Recognize\FilemanagerBundle\Tests\Response;

use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Recognize\FilemanagerBundle\Exception\ConflictException;
use Recognize\FilemanagerBundle\Response\FilemanagerResponseBuilder;
use Recognize\FilemanagerBundle\Service\FilemanagerService;
use Recognize\FilemanagerBundle\Tests\MockFiledataSynchronizer;
use Recognize\FilemanagerBundle\Tests\MockFileSecurityContext;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Tests\FilesystemTestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response;

class FilemanagerResponseBuilderTest extends FilesystemTestCase {

    /** @var FilemanagerResponseBuilder $builder */
    private $builder;

    /** @var FilemanagerService $filemanager */
    private $filemanager;

    public function setUp(){
        parent::setUp();

        $this->builder = new FilemanagerResponseBuilder();
        $this->filemanager = new FilemanagerService( array("default_directory" => $this->workspace),
            new MockFileSecurityContext(), new MockFiledataSynchronizer() );

        // Fill the test directory
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "testing" . DIRECTORY_SEPARATOR . "level2" . DIRECTORY_SEPARATOR . "level3" , 0777, true);
        mkdir( $this->workspace . DIRECTORY_SEPARATOR . "testing2" , 0777);
        file_put_contents($this->workspace . DIRECTORY_SEPARATOR . 'testing.txt', "TEST CONTENTS");
    }

    public function testEmptyResponse(){
        $actualresponse = $this->filterUnreliableFiledata( $this->builder->build() );
        $this->assertEquals( $this->getExpectedEmptyResponse(), $actualresponse );
    }

    public function testMultipleFilesResponse(){
        $files = $this->filemanager->getDirectoryContents("");
        $this->builder->addFiles( $files );

        $actualresponse = $this->filterUnreliableFiledata( $this->builder->build() );
        $this->assertEquals( $this->getExpectedMultipleFilesresponse(), $actualresponse );
    }

    public function testSingleFileResponse(){
        $files = $this->filemanager->getDirectoryContents("");
        $this->builder->addFile( $files[ 0 ] );

        $actualresponse = $this->filterUnreliableFiledata( $this->builder->build() );
        $this->assertEquals( $this->getExpectedSingleFileresponse(), $actualresponse );
    }

    public function testResponseFailure(){
        $this->builder->fail( "TESTMESSAGE", 500 );

        $actualresponse = $this->filterUnreliableFiledata( $this->builder->build() );
        $this->assertEquals( $this->getExpectedFailedResponse(), $actualresponse );
    }

    public function testResponseTranslation(){
        $files = $this->filemanager->getDirectoryContents("");
        $this->builder->addFile( $files[ 0 ] );
        $this->builder->setTranslationFunction(function( $file ){
            $file['name'] = "translated";
            return $file;
        });

        $actualresponse = $this->filterUnreliableFiledata( $this->builder->build() );
        $this->assertEquals( $this->getTranslatedResponse(), $actualresponse );
    }

    /**
     * @return Response
     */
    protected function getExpectedEmptyResponse(){
        $responsedata = array(
            "status" => "success",
            "data" => array()
        );

        return new Response( json_encode( $responsedata ) );
    }

    /**
     * @return Response
     */
    protected function getExpectedMultipleFilesresponse(){
        $responsedata = array(
            "status" => "success",
            "data" => array(
                "contents" => array(
                    array(
                        "name" => "testing.txt",
                        "directory" => "",
                        "path" => "testing.txt",
                        "file_extension" => "txt",
                        "type" => "file"
                    ),
                    array(
                        "name" => "testing2",
                        "directory" => "",
                        "path" => "testing2",
                        "file_extension" => "",
                        "type" => "dir"
                    ),
                    array(
                        "name" => "testing",
                        "directory" => "",
                        "path" => "testing",
                        "file_extension" => "",
                        "type" => "dir"
                    )
                )
            )
        );
        return new Response( json_encode( $responsedata ) );
    }

    /**
     * @return Response
     */
    protected function getExpectedSingleFileresponse(){
        $responsedata = array(
            "status" => "success",
            "data" => array(
                "contents" => array(
                    array(
                        "name" => "testing.txt",
                        "directory" => "",
                        "path" => "testing.txt",
                        "file_extension" => "txt",
                        "type" => "file"
                    )
                )
            )
        );
        return new Response( json_encode( $responsedata ) );
    }

    /**
     * @return Response
     */
    protected function getTranslatedResponse(){
        $responsedata = array(
            "status" => "success",
            "data" => array(
                "contents" => array(
                    array(
                        "name" => "translated",
                        "directory" => "",
                        "path" => "testing.txt",
                        "file_extension" => "txt",
                        "type" => "file"
                    )
                )
            )
        );
        return new Response( json_encode( $responsedata ) );
    }

    /**
     * @return Response
     */
    protected function getExpectedFailedResponse(){
        $responsedata = array(
            "status" => "failed",
            "data" => array(
                "message" => "TESTMESSAGE"
            )
        );
        $response = new Response( json_encode( $responsedata ) );
        $response->setStatusCode( 500, "TESTMESSAGE" );

        return $response;
    }

    /**
     * Make sure we filter the time and filesize data to make the tests less unreliable
     *
     * @param Response $response
     * @return mixed
     */
    protected function filterUnreliableFiledata( Response $response ){
        $response_content = json_decode( $response->getContent() );

        if( is_array( $response_content->data ) == false && property_exists( $response_content->data, "contents" ) ){
            $filecontents = $response_content->data->contents;
            for( $i = 0, $length = count( $filecontents ); $i < $length; $i++ ){
                unset( $filecontents[$i]->date_modified );
                unset( $filecontents[$i]->size );
            }

            $response_content->data->contents = $filecontents;
            $response->setContent( json_encode( $response_content ) );
        }

        return $response;
    }
}