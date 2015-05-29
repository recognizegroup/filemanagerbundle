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

    public function testAddCopyNumberToFileWithoutExtension(){
        $this->assertEquals("filepath(2)", PathUtils::addCopyNumber( "filepath", 2 ));
    }

    public function testAddCopyNumberToFileWithExtension(){
        $this->assertEquals("filepath(2).txt", PathUtils::addCopyNumber( "filepath.txt", 2 ));
    }

    public function testAddCopyNumberToFileWithoutExtensionWithCopyNumber(){
        $this->assertEquals("filepath(1000)", PathUtils::addCopyNumber( "filepath(2)", 1000 ));
    }

    public function testAddCopyNumberToFileWithCopyNumber(){
        $this->assertEquals("filepath(1000).txt", PathUtils::addCopyNumber( "filepath(2).txt", 1000 ));
    }

    public function testAddCopyNumberToFileWithSmallerCopyNumber(){
        $this->assertEquals("filepath(100).txt", PathUtils::addCopyNumber( "filepath(2).txt", 100 ));
    }

    public function testAddCopyNumberToFileWithMultipleDotsInThePath(){
        $this->assertEquals("derp.flerp.herp.filepath(99).txt", PathUtils::addCopyNumber( "derp.flerp.herp.filepath(2).txt", 99 ));
    }


}