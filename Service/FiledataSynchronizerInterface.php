<?php
namespace Recognize\FilemanagerBundle\Service;

use Recognize\FilemanagerBundle\Response\FileChanges;

interface FiledataSynchronizerInterface {

    public function synchronize( FileChanges $changes, $working_directory );

}