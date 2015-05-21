<?php
namespace Recognize\FilemanagerBundle\Security;

interface FileSecurityContextInterface {

    /**
     * Check if an action is granted
     *
     * @param string $action                   The action to check
     * @param string $working_directory        The working direcotry
     * @param string $relativepath             The relative path from the working directory
     * @return boolean
     */
    public function isGranted( $action, $working_directory, $relativepath );

}