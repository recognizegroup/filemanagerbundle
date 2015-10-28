<?php
namespace Recognize\FilemanagerBundle\Security;

use Recognize\FilemanagerBundle\Entity\Directory;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ConfigurationAuthorizationChecker implements AuthorizationCheckerInterface {

    private $patterns = array();
    private $roles = array();

    /**
     * Parses an array of access control objects
     *
     * @param $access_control
     */
    public function __construct( $access_control ){
        $roles = array();
        $this->patterns = $access_control;
    }

    /**
     * Set the roles of the user that is currently logged in
     *
     * @param array $roles
     */
    public function setCurrentRoles( array $roles ){
        $this->roles = $roles;
    }


    /**
     * Checks if the attributes are granted against the current authentication token and optionally supplied object.
     *
     * @param mixed $attributes
     * @param mixed Directory $object
     *
     * @return bool
     */
    public function isGranted($attributes, $object = null) {
        $granted = false;
        $required_mask = DirectoryMaskBuilder::getMaskFromValues( array( strtolower( $attributes ) ) );
        if( $object instanceof Directory ){
            for( $i = 0, $length = count( $this->patterns ); $i < $length; $i++ ){

                $pattern = $this->patterns[ $i ];
                if( $this->directoryMatchesAccessObject( $object, $pattern) ){

                    // Check if there are any roles in the access list that match the roles set from the user
                    if( count( array_intersect( $this->roles, $pattern['roles'] ) ) > 0 ){

                        // Check if the user mask matches the required mask
                        $user_access_mask = DirectoryMaskBuilder::getMaskFromValues( $pattern['actions'] );
                        if( 0 !== ($user_access_mask & $required_mask) ){
                            $granted = true;
                            break;
                        }
                    }
                }
            }
        }

        return $granted;
    }

    /**
     * Check if the directory matches the access control object - Needs a matching relative path and working directory
     *
     * @param Directory $directory
     * @param $access_control
     */
    protected function directoryMatchesAccessObject( Directory $directory, $access_control ){
        $escaped_regex = "/" . str_replace( "/", "\/", $access_control['path'] ) . "/";

        return $directory->getWorkingDirectoryName() == $access_control['directory']
            && preg_match( $escaped_regex, "/" . $directory->getRelativePath() );
    }

}