<?php
namespace Recognize\FilemanagerBundle\Tests\Service;

use InvalidArgumentException;
use Recognize\FilemanagerBundle\Entity\Directory;
use Recognize\FilemanagerBundle\Security\FileSecurityContext;
use Recognize\FilemanagerBundle\Service\FileACLManagerService;
use Recognize\FilemanagerBundle\Tests\MockUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class FileACLManagerServiceTest extends KernelTestCase {

    /** @var FileACLManagerService */
    private $aclmanager;

    private $container;

    /** @var Directory */
    private $rootdir;

    public function setUp(){
        self::bootKernel();
        $this->container = static::$kernel->getContainer();
        $this->aclmanager = new FileACLManagerService( $this->container->get('security.acl.provider') );

        $em = $this->container->get('doctrine')->getManager();
        $dir_repository = $em->getRepository('RecognizeFilemanagerBundle:Directory');
        $this->rootdir = $dir_repository->findOneBy(array(
            "working_directory" => "aclroot/",
            "relative_path" => "",
            "name" => ""
        ));
    }

    public function testACLStateIsProperlySet(){
        $context = $this->getFileSecurityContext();
        $this->assertFalse( $context->isGranted("OPEN", "aclroot/", "") );
    }

    /**
     * @depends testACLStateIsProperlySet
     */
    public function testGrantAccessToDirectory(){
        $this->aclmanager->grantAccessToDirectory( $this->rootdir, array("ROLE_TESTACLUSER"), array("OPEN") );

        $context = $this->getFileSecurityContext();
        $this->assertTrue( $context->isGranted("OPEN", "aclroot/", "") );
    }

    /**
     * @depends testGrantAccessToDirectory
     */
    public function testGrantDeleteAccessToDirectoryWithExistingACLS(){
        $this->aclmanager->grantAccessToDirectory( $this->rootdir, array("ROLE_TESTACLUSER"), array("DELETE") );

        $context = $this->getFileSecurityContext();
        $delete = "Not granted";
        if( $context->isGranted("DELETE", "aclroot/", "") ){
            $delete = "Delete granted";
        }

        $open = "Not granted";
        if( $context->isGranted("OPEN", "aclroot/", "") ){
            $open = "Open granted";
        }

        $this->assertEquals( "Delete granted", $delete );
        $this->assertEquals( "Open granted", $open );
    }


    /**
     * @depends testGrantDeleteAccessToDirectoryWithExistingACLS
     */
    public function testDisallowAccessToDirectoryWithExistingACLS(){
        $this->aclmanager->denyAccessToDirectory( $this->rootdir, array("ROLE_TESTACLUSER"), array("OPEN") );

        $context = $this->getFileSecurityContext();
        $this->assertFalse( $context->isGranted("OPEN", "aclroot/", "") );
    }

    /**
     * @depends testDisallowAccessToDirectoryWithExistingACLS
     */
    public function testDisallowDeleteAccessToDirectoryWithExistingACLS(){
        $this->aclmanager->denyAccessToDirectory( $this->rootdir, array("ROLE_TESTACLUSER"), array("DELETE") );

        $context = $this->getFileSecurityContext();
        $delete = "Not disallowed";
        if( $context->isGranted("DELETE", "aclroot/", "") == false ){
            $delete = "Delete disallowed";
        }

        $open = "Not disallowed";
        if( $context->isGranted("OPEN", "aclroot/", "") == false ){
            $open = "Open disallowed";
        }

        $this->assertEquals( "Delete disallowed", $delete );
        $this->assertEquals( "Open disallowed", $open );
    }

    /**
     * @depends testDisallowDeleteAccessToDirectoryWithExistingACLS
     */
    public function testGrantAccessToDirectoryWithExistingDisallowedACLS(){
        $this->aclmanager->grantAccessToDirectory( $this->rootdir, array("ROLE_TESTACLUSER"), array("OPEN") );

        $context = $this->getFileSecurityContext();
        $this->assertTrue( $context->isGranted("OPEN", "aclroot/", "") );
    }

    /**
     * @depends testGrantAccessToDirectoryWithExistingDisallowedACLS
     */
    public function testClearDirectoryACLS(){
        $this->aclmanager->clearAccessRightsForDirectory( $this->rootdir );

        $context = $this->getFileSecurityContext();
        $this->assertFalse( $context->isGranted("OPEN", "aclroot/", "") );
    }

    /**
     * @depends testClearDirectoryACLS
     */
    public function testClearDirectoryACLSForSpecificRole(){
        $this->aclmanager->grantAccessToDirectory( $this->rootdir, array("ROLE_TESTACLUSER", "ROLE_TESTADMIN"), array("OPEN") );

        $context = $this->getFileSecurityContext();
        $this->assertTrue( $context->isGranted("OPEN", "aclroot/", ""), "Grant access didn't work - Required to test clearing for single role" );

        $this->aclmanager->clearAccessToDirectory( $this->rootdir, array("ROLE_TESTACLUSER") );

        $user = new MockUser("testadminuser", array("ROLE_TESTADMIN"));
        $context = $this->getFileSecurityContext( $user );
        $this->assertTrue( $context->isGranted("OPEN", "aclroot/", ""), "Other roles' ACLs were incorrectly cleared where only TESTACLUSER role ACLS should be cleared" );

        $context = $this->getFileSecurityContext();
        $this->assertFalse( $context->isGranted("open", "aclroot/", ""), "Test users ACLs weren't cleared properly" );
    }


    /**
     * @depends testACLStateIsProperlySet
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDirectoryOnAccessGranted(){
        $this->aclmanager->grantAccessToDirectory( new Directory(), array("ROLE_TESTACLUSER"), array("EDIT") );
    }

    /**
     * @depends testACLStateIsProperlySet
     * @expectedException InvalidArgumentException
     */
    public function testInvalidDirectoryOnAccessDenied(){
        $this->aclmanager->denyAccessToDirectory( new Directory(), array("ROLE_TESTACLUSER"), array("EDIT") );
    }


    // ----------------------------------- UTILS --------------------------------------

    protected function getFileSecurityContext( $user = false ){
        $em = $this->container->get('doctrine')->getManager();
        $dir_repository = $em->getRepository('RecognizeFilemanagerBundle:Directory');
        $aclprovider = $this->container->get('security.acl.provider');

        $tokenstorage = new TokenStorage();
        if( $user == false ){
            $user = $this->getTestUser();
        }

        $tokenstorage->setToken( new PreAuthenticatedToken( $user, true, true, $user->getRoles() ) );

        return new FileSecurityContext($this->getConfig(), $aclprovider, $tokenstorage, $dir_repository, false );
    }

    protected function getTestUser(){
        return new MockUser("testacluser", array("ROLE_TESTACLUSER"));
    }

    protected function getConfig(){
        return array("security" => "enabled",
            "access_control" => array( array( "directory" => "default", "path" => "^/$", "actions" => array ( 'OPEN', 'DELETE' ),
                "roles" => array("ROLE_TESTUSER", "ROLE_TESTADMIN") )
            )
        );
    }
}