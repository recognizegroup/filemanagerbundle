<?php
namespace Recognize\FilemanagerBundle\Service;

use Recognize\FilemanagerBundle\Entity\FileReference;

interface ThumbnailGeneratorInterface {

    public function generateThumbnailForFile( Filereference $ref = null );

    public function generateThumbnailForFilepath( $working_directory, $relative_path );

}