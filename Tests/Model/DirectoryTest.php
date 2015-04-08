<?php
namespace Recognize\FilemanagerBundle\Tests\Model;

use Recognize\FilemanagerBundle\Model\Directory;

class DirectoryTest extends \PHPUnit_Framework_TestCase {

    public function testNonexistingDirectory(){
        $dir = new Directory( "/tmpasdfasdf", "ffddapp", 1 );

        $this->assertEquals( $this->expectedEmptyDirectoryFiles() , $dir->getChildren() );
        $this->assertEquals( "/tmpasdfasdf", $dir->getPath() );
        $this->assertEquals( "ffddapp", $dir->getFilename() );
    }

    protected function expectedEmptyDirectoryFiles(){
        $files = array();
        return $files;
    }


}