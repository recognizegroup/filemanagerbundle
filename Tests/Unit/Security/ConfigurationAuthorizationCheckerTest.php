<?php
namespace Recognize\FilemanagerBundle\Tests\Response;

use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Security\ConfigurationAuthorizationChecker;
use Recognize\FilemanagerBundle\Security\FileSecurityContext;

class ConfigurationAuthorizationCheckerTest extends \PHPUnit_Framework_TestCase {

    /** @var ConfigurationAuthorizationChecker $checker */
    private $checker;

    public function setUp(){
        parent::setUp();

        $this->checker = new ConfigurationAuthorizationChecker( array() );
    }

    public function testEmptyMask(){
        $this->assertFalse( $this->checker->isGranted("open") );
    }

    public function testDisallowedUpload(){
        $this->assertFalse( $this->checker->isGranted("upload") );
    }

    public function testDisallowedCreate(){
        $this->assertFalse( $this->checker->isGranted("create") );
    }

    public function testDisallowedRename(){
        $this->assertFalse( $this->checker->isGranted("rename") );
    }

    public function testDisallowedMove(){
        $this->assertFalse( $this->checker->isGranted("move") );
    }

    public function testDisallowedDelete(){
        $this->assertFalse( $this->checker->isGranted("delete") );
    }

    public function testNotMaskOwner(){
        $this->assertFalse( $this->checker->isGranted("mask_owner") );
    }


    public function testAllowedOpen(){
        $roles = array("ROLE_USER");
        $checker = new ConfigurationAuthorizationChecker( array( array( "path" => "^/tes",
            "directory" => "default", "roles" => $roles, "actions" => array( "open" ) ) ) );
        $checker->setCurrentRoles( $roles );

        $directory = new Directory();
        $directory->setDirectoryName("test");
        $directory->setWorkingDirectory("");
        $directory->setWorkingDirectoryName("default");
        $directory->setRelativePath("test");

        $this->assertTrue( $checker->isGranted("open", $directory) );
    }

    public function testDisallowedOpen(){
        $directory = new Directory();
        $directory->setDirectoryName("test");
        $directory->setWorkingDirectory("");
        $directory->setWorkingDirectoryName("default");
        $directory->setRelativePath("test");

        $roles = array("ROLE_ADMIN");
        $checker = new ConfigurationAuthorizationChecker( array( array( "path" => "^/te",
            "directory" => "default", "roles" => $roles, "actions" => array( "open" ) ) ) );
        $checker->setCurrentRoles( array("ROLE_USER") );

        $this->assertFalse( $checker->isGranted("open", $directory) );
    }

    public function testAllowedOpenWithEmptyActions(){
        $directory = new Directory();
        $directory->setDirectoryName("test");
        $directory->setWorkingDirectory("");
        $directory->setWorkingDirectoryName("default");
        $directory->setRelativePath("test");

        $roles = array("ROLE_USER");
        $checker = new ConfigurationAuthorizationChecker( array( array( "path" => "^/te",
            "directory" => "default", "roles" => $roles, "actions" => array() ) ) );
        $checker->setCurrentRoles( array("ROLE_USER") );

        $this->assertTrue( $checker->isGranted("open", $directory) );
    }


    public function testMultipleRoleAllowedOpen(){
        $directory = new Directory();
        $directory->setDirectoryName("test");
        $directory->setWorkingDirectory("");
        $directory->setWorkingDirectoryName("default");
        $directory->setRelativePath("test");

        $roles = array("ROLE_ADMIN");
        $checker = new ConfigurationAuthorizationChecker( array( array( "path" => "^/",
            "directory" => "default", "roles" => $roles, "actions" => array( "open" ) ) ) );
        $checker->setCurrentRoles( array("ROLE_USER", "ROLE_ADMIN", "ROLE_SUPER_ADMIN") );

        $this->assertTrue( $checker->isGranted("open", $directory) );
    }
}