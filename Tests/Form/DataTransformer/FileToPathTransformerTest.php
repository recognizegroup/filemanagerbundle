<?php
namespace Recognize\FilemanagerBundle\Tests\Response;

use Recognize\FilemanagerBundle\Entity\FileReference;
use Recognize\FilemanagerBundle\Form\DataTransformer\FileToPathTransformer;

class FilToPathTransformerTest extends \PHPUnit_Framework_TestCase {

    public function testEmptyValue(){
        $transformer = new FileToPathTransformer();

        $expected = null;
        $this->assertEquals( $expected, $transformer->transform( null ));
    }

    public function testRealValue(){
        $transformer = new FileToPathTransformer();

        $filename = "testpath.php";

        $fileref = new FileReference();
        $fileref->setFileName( $filename );

        $expected = $filename;
        $this->assertEquals( $expected, $transformer->transform( $fileref ));
    }


}