<?php
namespace Recognize\FilemanagerBundle\Response;

use Symfony\Component\Finder\SplFileInfo;

/**
 * Container for file changes happened through the FilemanagerAPI
 *
 * Class FileChanges
 * @package Recognize\FilemanagerBundle\Response
 */
class FileChanges implements \JsonSerializable {

    protected $type;
    protected $oldfile;
    protected $newfile = null;

    public function __construct( $type, $oldfile ){
        $this->type = $type;
        $this->oldfile = $oldfile;
    }

    public function setFileAfterChanges( $file ){
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

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    public function jsonSerialize() {
        $data = array();
        $data['type'] = $this->type;
        $data['file'] = $this->transformFileToData( $this->oldfile );

        if( $this->newfile !== null ){
            $data['updatedfile'] = $this->transformFileToData( $this->newfile );
        }
        return $data;
    }
}