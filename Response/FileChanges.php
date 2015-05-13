<?php
namespace Recognize\FilemanagerBundle\Response;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Container for file changes happened through the FilemanagerAPI
 *
 * Class FileChanges
 * @package Recognize\FilemanagerBundle\Response
 */
class FileChanges {

    protected $type;

    /** @var SplFileInfo */
    protected $oldfile;
    protected $preloaded_oldfile = null;

    /** @var SplFileInfo */
    protected $newfile = null;

    /**
     * @param string $type                  The type of change done - For example rename
     * @param SplFileInfo $oldfile
     */
    public function __construct( $type, SplFileInfo $oldfile ){
        $this->type = $type;
        $this->oldfile = $oldfile;
    }

    /**
     * We should be able to preload the file to be changed before the change takes place
     * To make sure we still have data even if the file is deleted or moved
     */
    public function preloadOldfileData(){
        $this->preloaded_oldfile = $this->transformFileToData( $this->oldfile );
    }

    /**
     * @param SplFileInfo $file
     */
    public function setFileAfterChanges( SplFileInfo $file ){
        $this->newfile = $file;
    }

    public function getUpdatedFile(){
        return $this->newfile;
    }

    public function getFile(){
        return $this->oldfile;
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
        if( $file->isFile() || $file->isDir() ){
            $date->setTimestamp( $file->getMTime() );
            $filedata['size'] = $file->getSize();
        } else {
            $filedata['size'] = 0;
        }

        $filedata['date_modified'] = $date->format("Y-m-d H:i:s");

        return $filedata;
    }

    /**
     * Turn the filechanges into an array that can be JSON encoded
     *
     * @return array
     */
    public function toArray(){
        $data = array();
        $data['type'] = $this->type;
        if( $this->preloaded_oldfile == null ){
            $data['file'] = $this->transformFileToData( $this->oldfile );
        } else {
            $data['file'] = $this->preloaded_oldfile;
        }

        if( $this->newfile !== null ){
            $data['updatedfile'] = $this->transformFileToData( $this->newfile );
        }
        return $data;
    }

    /**
     * Get the type of change done to the database
     *
     * @return mixed
     */
    public function getType(){
        return $this->type;
    }

}