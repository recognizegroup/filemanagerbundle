<?php
namespace Recognize\FilemanagerBundle\Response;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response;

class FilemanagerResponseBuilder {

    protected $config = array("status" => "success", "data" => array());

    protected $statuscode = 200;
    protected $error_message = "";

    protected $translation_function = null;

    /**
     * Fail the response and return a detailed message
     *
     * @param $error_message      The error message to display to the requester
     * @param $statuscode         Optional: the statuscode to return
     *
     * @return FilemanagerResponseBuilder
     */
    public function fail( $error_message, $statuscode = 400 ){
        $this->statuscode = $statuscode;
        $this->error_message = $error_message;

        $this->config["status"] = "failed";
        return $this->add( "message", $error_message );
    }

    /**
     * Add multiple files to the response
     *
     * @param SplFileInfo[] $files
     */
    public function addFiles( array $files ){
        if( isset($this->config['data']['contents']) == false ){
            $this->config['data']['contents'] = array();
        }

        for( $i = 0, $length = count($files); $i < $length; $i++ ){
            $this->addFile( $files[$i] );
        }

        return $this;
    }

    /**
     * Add a single file to the response
     *
     * @param SplFileInfo $file
     */
    public function addFile( $file ){
        if( isset($this->config['data']['contents']) == false ){
            $this->config['data']['contents'] = array();
        }

        $this->config['data']['contents'][] = $this->transformFileToData( $file );
        return $this;
    }

    /**
     * Set the translation function that will be called right before the response is made
     *
     * @param callable $translate
     */
    public function setTranslationFunction( callable $translate ){
        $this->translation_function = $translate;
    }

    /**
     * Translate the files
     */
    protected function translateFiles(){
        $contents = $this->getContents();
        if( count($contents) > 0 && $this->translation_function !== null ){

            /** @var callable $translate */
            $translate = $this->translation_function;

            $translated_contents = array();
            for( $i = 0, $length = count( $contents ); $i < $length; $i++ ){
                $translated_contents[] = $translate( $contents[$i] );
            }
            $this->config['data']['contents'] = $translated_contents;
        }
    }

    /**
     * Takes a file and turns it into data expected by the API
     *
     * @param SplFileInfo $file
     */
    protected function transformFileToData( SplFileInfo $file ){
        $filedata = array();
        $filedata['name'] = $file->getFilename();
        $filedata['directory'] = $file->getRelativePath();
        $filedata['path'] = $file->getRelativePath() . $file->getFilename();

        $filedata['file_extension'] = $file->getExtension();
        if( $file->isDir() ){
            $filedata['type'] = "dir";
        } else {
            $filedata['type'] = "file";
        }

        $date = new \DateTime();
        $date->setTimestamp( $file->getMTime() );
        $filedata['date_modified'] = $date->format("Y-m-d H:i:s");
        $filedata['size'] = $file->getSize();

        return $filedata;
    }

    /**
     * Returns all the files in this response
     *
     * @return array
     */
    protected function getContents(){
        $files = array();
        if( isset( $this->config['data']['contents'] ) ){
            $files = $this->config['data']['contents'];
        }
        return $files;
    }

    // ----------------------------------------------------- STANDARD BUILDER METHODS

    /**
     * Add a value to the request data
     *
     * @param string $key           The key to add
     * @param $value                The value linked to the key
     * @return FilemanagerResponseBuilder
     */
    public function add( $key, $value ){
        $this->config["data"][$key] = $value;
        return $this;
    }

    /**
     * Create the response required for the FileManager
     *
     * @return Response
     */
    public function build(){
        $this->translateFiles();

        $response = new Response( json_encode( $this->config ) );
        if( $this->statuscode != 200 ){
            $response->setStatusCode( $this->statuscode, $this->error_message );
        }
        return $response;
    }

}