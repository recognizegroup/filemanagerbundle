<?php
namespace Recognize\FilemanagerBundle\Tests;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\UserInterface;

class MockUser implements  UserInterface {

    protected $roles;
    protected $username;

    public function __construct( $username, $roles = array() ){
        $this->roles = $roles;
        $this->username = $username;
    }

    public function getRoles() {
        return $this->roles;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword() {
        // TODO: Implement getPassword() method.
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt() {
        // TODO: Implement getSalt() method.
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername() {
        return $this->username;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials() {
        // TODO: Implement eraseCredentials() method.
    }

    public function getGroups(){
        return array();
    }
}