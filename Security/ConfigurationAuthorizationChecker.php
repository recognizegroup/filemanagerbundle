<?php
namespace Recognize\FilemanagerBundle\Security;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ConfigurationAuthorizationChecker implements AuthorizationCheckerInterface {

    private $rolemasks = array();
    private $roles = array();

    /**
     * Parses an array of actions with the allowed roles into ACEs that are used by the security context
     *
     * @param $actions
     */
    public function __construct( $actions ){
        $roles = array();

        // Turn the action: [roles] into role: [actions]
        $action_names = array_keys( $actions );
        for( $i = 0, $length = count($action_names); $i < $length; $i++ ){
            $allowed_action = $action_names[ $i ];

            $allowed_roles = $actions[ $allowed_action ];
            for( $j = 0, $jlength = count( $allowed_roles); $j < $jlength; $j++ ){
                $allowed_role = $allowed_roles[$j];

                if( isset( $roles[ $allowed_role ]) == false ){
                    $roles[ $allowed_role ] = array();
                }

                $roles[ $allowed_role ][] = $allowed_action;
            }
        }

        // Turn the actions into bitmasks
        $role_names = array_keys( $roles );
        for( $i = 0, $length = count( $role_names ); $i < $length; $i++ ){
            $this->rolemasks[ $role_names[$i] ] = DirectoryMaskBuilder::getMaskFromValues( $roles[ $role_names[$i] ] );
        }
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
     * @param mixed $object
     *
     * @return bool
     */
    public function isGranted($attributes, $object = null) {
        $granted = false;
        $required_mask = DirectoryMaskBuilder::getMaskFromValues( array( strtolower( $attributes ) ) );
        for( $i = 0, $length = count( $this->roles ); $i < $length; $i++ ){

            $role = $this->roles[$i];
            if( isset( $this->rolemasks[ $role ] ) && 0 !== ($this->rolemasks[ $role ] & $required_mask) ){
                $granted = true;
                break;
            }

        }

        return $granted;
    }

}