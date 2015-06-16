<?php
namespace Recognize\FilemanagerBundle\Service;

use Recognize\FilemanagerBundle\Entity\FileReference;

interface ThumbnailGeneratorInterface {

    public function generateThumbnailForFile( Filereference $ref = null );

}