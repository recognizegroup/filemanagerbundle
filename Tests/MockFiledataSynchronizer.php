<?php
namespace Recognize\FilemanagerBundle\Tests;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Response\FileChanges;
use Recognize\FilemanagerBundle\Service\FiledataSynchronizerInterface;

class MockFiledataSynchronizer implements FiledataSynchronizerInterface {

    private $filereference;

    public function __construct( $fileReference = null ){
        $this->filereference = $fileReference;
    }

    public function synchronize(FileChanges $changes, $working_directory){
        // Do nothing
    }

    public function loadFileReference($working_directory, $relativepath) {
        if( $this->filereference !== null ){
            return $this->filereference;
        }
    }

    public function canDirectoryBeDeletedFromTheFilesystem() {
        return true;
    }

    public function deleteDirectory( Directory $directory) {
        return true;
    }

    public function deleteFileReference( FileReference $reference) {
        return true;
    }
}
