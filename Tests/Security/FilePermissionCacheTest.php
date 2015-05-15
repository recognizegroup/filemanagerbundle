<?php
namespace Recognize\FilemanagerBundle\Tests\Response;

use Recognize\FilemanagerBundle\Security\ConfigurationAuthorizationChecker;
use Recognize\FilemanagerBundle\Security\FilePermissionCache;
use Recognize\FilemanagerBundle\Security\FileSecurityContext;

class FilePermissionCacheTest extends \PHPUnit_Framework_TestCase {

    public function setUp(){
        parent::setUp();
    }

    public function testEmptyCache(){
        $cache = new FilePermissionCache();

        $this->assertFalse( $cache->isCached("open", "/") );
    }

    public function testCommitSinglePath(){
        $cache = new FilePermissionCache();
        $cache->stagePath("test");
        $cache->commitResultsForStagedPaths("open", true);

        $this->assertTrue( $cache->isGranted("open", "test") );
    }

    public function testNotGranted(){
        $cache = new FilePermissionCache();
        $cache->stagePath("test");
        $cache->commitResultsForStagedPaths("open", false);

        $this->assertFalse( $cache->isGranted("open", "test") );
    }

    public function testMultiplePathCommit(){
        $cache = new FilePermissionCache();
        $cache->stagePath("test");
        $cache->stagePath("test/test");
        $cache->commitResultsForStagedPaths("open", true);

        $this->assertTrue( $cache->isGranted("open", "test") && $cache->isGranted("open", "test/test") );
    }
}