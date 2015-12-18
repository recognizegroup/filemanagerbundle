<?php
namespace Recognize\FilemanagerBundle\Service;

use Recognize\FilemanagerBundle\Entity\FileReference;

interface ThumbnailGeneratorInterface {

    public function generateThumbnailForFile( Filereference $ref = null );

    public function generateThumbnailForFilepath( $working_directory, $relative_path );

    /**
     * Return the method of thumbnail generation
     *
     * Either ALL for generating thumbnails based on the filepath,
     * or indexed only where the thumbnails only get generated if they are uploaded through the filemanager
     *
     * @return string
     */
    public function getThumbnailStrategy();


}