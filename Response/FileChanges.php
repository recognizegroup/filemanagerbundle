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
        $date->setTimestamp( $file->getMTime() );
        $filedata['date_modified'] = $date->format("Y-m-d H:i:s");
        $filedata['size'] = $file->getSize();

        return $filedata;
    }

    public function toArray(){
        $data = array();
        $data['type'] = $this->type;
        $data['file'] = $this->transformFileToData( $this->oldfile );

        if( $this->newfile !== null ){
            $data['updatedfile'] = $this->transformFileToData( $this->newfile );
        }
        return $data;
    }

}