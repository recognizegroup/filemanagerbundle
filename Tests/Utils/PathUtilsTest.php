<?php
namespace Recognize\FilemanagerBundle\Tests\Response;


use Recognize\FilemanagerBundle\Utils\PathUtils;

class PathUtilsTest extends \PHPUnit_Framework_TestCase {

    public function testEmptyPath(){
        $this->assertEquals("", PathUtils::addTrailingSlash( "" ));
    }

    public function testSimplePath(){
        $this->assertEquals("path/", PathUtils::addTrailingSlash( "path" ));
    }

    public function testPathWithSlash(){
        $this->assertEquals("path/", PathUtils::addTrailingSlash( "path/" ));
    }

    public function testMovingUpInRoot(){
        $this->assertEquals("", PathUtils::moveUpPath( "" ));
    }

    public function testMovingUpOnce(){
        $this->assertEquals("/", PathUtils::moveUpPath( "path/" ));
    }

    public function testMovingUpInvalidPath(){
        $this->assertEquals("/", PathUtils::moveUpPath( "path////" ));
    }

    public function testMovingUpPathWithoutTrailingSlash(){
        $this->assertEquals("/", PathUtils::moveUpPath( "path" ));
    }

    public function testMovingUpDeepPath(){
        $this->assertEquals("/path/path/", PathUtils::moveUpPath( "path/path/path" ));
    }

    public function testGetLastRootNode(){
        $this->assertEquals("", PathUtils::getLastNode( "/" ));
    }

    public function testGetLastNodeName(){
        $this->assertEquals("bla", PathUtils::getLastNode( "/bla/" ));
    }

    public function testGetLastDeepNodeName(){
        $this->assertEquals("haxx", PathUtils::getLastNode( "/bla/test/haxx/" ));
    }

    public function testRemoveFirstSlash(){
        $this->assertEquals("bla/test/haxx/", PathUtils::removeFirstSlash( "/bla/test/haxx/" ));
    }

    public function testRemoveFirstSlashOneCharacter(){
        $this->assertEquals("", PathUtils::removeFirstSlash( "/" ));
    }

    public function testRemoveFirstSlashEmptyPath(){
        $this->assertEquals("", PathUtils::removeFirstSlash( "" ));
    }
}