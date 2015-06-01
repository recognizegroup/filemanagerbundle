<?php
namespace Recognize\FilemanagerBundle\Tests;

use Symfony\Component\Finder\SplFileInfo;

/**
 * SplFileInfo that mocks itself as a file
 *
 * Class MockSplFilInfo
 * @package Recognize\FilemanagerBundle\Tests
 */
class MockSplFileInfo extends SplFileInfo {

    private $mock_filename = "";

    private $is_file = false;

    public function setAsFile(){
        $this->is_file = true;
    }

    public function setAsDir(){
        $this->is_file = false;
    }

    public function isFile(){
        return $this->is_file;
    }

    public function isDir(){
        return $this->is_file == false;
    }

    public function getMTime(){
        return 0;
    }

    public function getSize(){
        return 0;
    }

    public function setFilename( $filename ){
        $this->mock_filename = $filename;
    }

    public function getFilename(){
        return $this->mock_filename;
    }

}