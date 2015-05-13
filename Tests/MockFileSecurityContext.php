<?php
namespace Recognize\FilemanagerBundle\Tests;

use Recognize\FilemanagerBundle\Security\FileSecurityContextInterface;

class MockFileSecurityContext implements FileSecurityContextInterface {

    /**
     * Check if an action is granted
     *
     * @param string $action                   The action to check
     * @param string $directory                The directory to apply the action to
     * @return boolean
     */
    public function isGranted( $action, $directory ){
        return true;
    }
}
