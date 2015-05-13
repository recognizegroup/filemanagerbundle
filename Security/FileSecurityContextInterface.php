<?php
namespace Recognize\FilemanagerBundle\Security;

interface FileSecurityContextInterface {

    /**
     * Check if an action is granted
     *
     * @param string $action                   The action to check
     * @param string $directory                The directory to apply the action to
     * @return boolean
     */
    public function isGranted( $action, $directory );

}