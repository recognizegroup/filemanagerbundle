<?php
namespace Recognize\FilemanagerBundle\Model;

class File extends FileNode {

    /**
     * An object representing a file on the filesystem
     *
     * @param string $path                     The path to the file
     * @param string $name                     The name of the file
     */
    public function __construct( $path, $filename ){
        $this->path = $path;
        $this->filename = $filename;
        $this->filetype = pathinfo( $filename, PATHINFO_EXTENSION );
    }

    public function getPath(){
        return $this->path;
    }

    public function getFilename(){
        return $this->filename;
    }

    public function getFiletype(){
        return $this->filetype;
    }

}