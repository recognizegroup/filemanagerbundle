<?php
namespace Recognize\FilemanagerBundle\Tests\Service;

use Doctrine\ORM\EntityManager;
use Recognize\FilemanagerBundle\Security\FileSecurityContext;
use Recognize\FilemanagerBundle\Tests\MockUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class FileSecurityContextTest extends KernelTestCase {

    /** @var FileSecurityContext */
    private $securitycontext;

    private $rootdir;

    private $dir_repository;
    private $file_repository;
    private $container;

    public function setUp(){
        self::bootKernel();
        $this->container = static::$kernel->getContainer();
    }

    public function testConfigAllowed(){
        $context = $this->getFileSecurityContext( $this->getAllowedAllConfig(),
            $this->getTestUser() );
        $this->assertTrue( $context->isGranted("open", "nonexistingroot/", "/") );
    }

    /**
     * @depends testConfigAllowed
     */
    public function testConfigDisallowed(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(),
            $this->getTestUser() );
        $this->assertFalse( $context->isGranted("open", "nonexistingroot/", "/") );
    }

    /**
     * @depends testConfigDisallowed
     */
    public function testWithoutConfigSecurity(){
        $context = $this->getFileSecurityContext( array(), $this->getTestUser() );
        $this->assertTrue( $context->isGranted("DELETE", "nonexistingroot/", "") );
    }

    /**
     * @depends testConfigDisallowed
     */
    public function testDisablingSecurity(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(), $this->getTestUser(), true);
        $this->assertTrue( $context->isGranted("DELETE", "nonexistingroot/", "") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testOpeningAllowedForUser(){
        $context = $this->getFileSecurityContext($this->getDisallowedAllConfig(), $this->getTestUser() );
        $this->assertTrue( $context->isGranted("OPEN", "testroot/", "") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testOpeningNotAllowedForNotLoggedInUser(){
        $context = $this->getFileSecurityContext($this->getAllowedAllConfig(), null );
        $this->assertFalse( $context->isGranted("OPEN", "testroot/", "") );
    }


    /**
     * @depends testDisablingSecurity
     */
    public function testOpeningAllowedForAdmin(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(), $this->getTestAdmin() );
        $this->assertTrue( $context->isGranted("OPEN", "testroot/", "") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testDeletingNotAllowedForUser(){
        $context = $this->getFileSecurityContext($this->getAllowedAllConfig(), $this->getTestUser() );
        $this->assertFalse( $context->isGranted("DELETE", "testroot/", "") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testDeletingUnindexedSubfolderNotAllowedForUser(){
        $context = $this->getFileSecurityContext($this->getAllowedAllConfig(), $this->getTestUser() );
        $this->assertFalse( $context->isGranted("DELETE", "testroot/", "nonexisting/nonexisting") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testOpeningDirectoryUnderRootNotAllowedForUser(){
        $context = $this->getFileSecurityContext( $this->getAllowedAllConfig(), $this->getTestUser() );
        $this->assertFalse( $context->isGranted("OPEN", "testroot/", "test/") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testOpeningDirectoryUnderRootAllowedForAdmin(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(), $this->getTestAdmin() );
        $this->assertTrue( $context->isGranted("OPEN", "testroot/", "test/") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testOpeningSubdirectoryNotAllowedForAdmin(){
        $context = $this->getFileSecurityContext( $this->getAllowedAllConfig(), $this->getTestAdmin() );
        $this->assertFalse( $context->isGranted("OPEN", "testroot/", "test/test") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testDeletingSubdirectoryAllowedForUser(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(), $this->getTestUser() );
        $this->assertTrue( $context->isGranted("DELETE", "testroot/", "test/test") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testDeletingNonexistingDirectoryUnderSubdirectoryAllowedForUser(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(), $this->getTestUser() );
        $this->assertTrue( $context->isGranted("DELETE", "testroot/", "test/test/asdfasdf/fdasfdas/asdfasdf") );
    }

    /**
     * @depends testDisablingSecurity
     */
    public function testDeletingExistingDirectoryUnderSubdirectoryWithoutACLSAllowedForUser(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(), $this->getTestUser() );
        $this->assertTrue( $context->isGranted("DELETE", "testroot/", "test/test/subtest") );
    }


    /**
     * @depends testDisablingSecurity
     */
    public function testIfCacheDoesntCrash(){
        $context = $this->getFileSecurityContext( $this->getDisallowedAllConfig(), $this->getTestUser() );
        $context->isGranted("DELETE", "testroot/", "test/test/asdfasdf/fdasfdas/asdfasdf");
        $this->assertTrue( $context->isGranted("DELETE", "testroot/", "test/test/asdfasdf/fdasfdas/asdfasdf") );
    }



    // ----------------------------------- UTILS --------------------------------------

    protected function getFileSecurityContext( $configuration = array(), $user = null, $always_auth = false ){
        $em = $this->container->get('doctrine')->getManager();
        $dir_repository = $em->getRepository('RecognizeFilemanagerBundle:Directory');
        $aclprovider = $this->container->get('security.acl.provider');

        $tokenstorage = new TokenStorage();
        if( $user != null ){
            $tokenstorage->setToken( new PreAuthenticatedToken( $user, true, true, $user->getRoles() ) );
        } else {
            $tokenstorage->setToken( null );
        }

        return new FileSecurityContext($configuration, $aclprovider, $tokenstorage, $dir_repository, $always_auth );
    }

    protected function getTestUser(){
        return new MockUser("testuser", array("ROLE_TESTUSER"));
    }

    protected function getTestAdmin(){
        return new MockUser("testadmin", array("ROLE_TESTADMIN"));
    }

    protected function getAllowedAllConfig(){
        return array("security" => "enabled",
            "access_control" => array( array( "directory" => "default", "path" => "^/$", "actions" => array ( 'OPEN', 'DELETE' ),
                "roles" => array("ROLE_TESTUSER", "ROLE_TESTADMIN"))
            )
        );

    }

    protected function getDisallowedAllConfig(){
        return array("security" => "enabled",
            "access_control" => array( array( "directory" => "default", "path" => "^/$", "actions" => array ( 'OPEN', 'DELETE' ),
                "roles" => array("") )
            )
        );
    }
}