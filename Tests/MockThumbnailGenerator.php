<?php
namespace Recognize\FilemanagerBundle\Tests;

use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Service\ThumbnailGeneratorInterface;

class MockThumbnailGenerator implements ThumbnailGeneratorInterface {

    public function generateThumbnailForFile( Filereference $ref = null ){
        return "";
    }


}