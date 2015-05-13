<?php
namespace Recognize\FilemanagerBundle\Tests;

use Recognize\FilemanagerBundle\Response\FileChanges;
use Recognize\FilemanagerBundle\Service\FiledataSynchronizerInterface;

class MockFiledataSynchronizer implements FiledataSynchronizerInterface {

    public function synchronize(FileChanges $changes, $working_directory){
        // Do nothing
    }
}
