<?php
namespace Recognize\FilemanagerBundle\Tests\Twig;

use Recognize\FilemanagerBundle\Twig\ConfigurationMerge;

class ConfigurationMergeTest extends \PHPUnit_Framework_TestCase {

    public function testSimpleMerge(){
        $func = new ConfigurationMerge();

        $array1 = array(
            "value" => true,
        );

        $array2 = array(
            "key" => false
        );

        $actualmerge = $func( $array1, $array2 );

        $this->assertEquals( $this->expectedSimpleMerge(), $actualmerge );
    }

    public function testRecursiveMerge(){
        $func = new ConfigurationMerge();

        $array1 = array(
            "test1" => array(
                "value" => "asdfasdf",
                "value2" => "jkdfjap"
            ),
            "value" => true
        );

        $array2 = array(
            "test1" => array(
                "value" => 1,
                "value2" => 2
            ),
            "key" => false
        );

        $actualmerge = $func( $array1, $array2 );

        $this->assertEquals( $this->expectedRecursiveMerge(), $actualmerge );
    }

    public function testRecursiveOverrideMerge(){
        $func = new ConfigurationMerge();

        $array1 = array(
            "test1" => array(
                "value" => "asdfasdf",
                "value2" => "jkdfjap"
            ),
            "value" => true
        );

        $array2 = array(
            "test1" => array(

            ),
            "key" => false
        );

        $actualmerge = $func( $array1, $array2 );

        $this->assertEquals( $this->expectedRecursiveOverrideMerge(), $actualmerge );
    }


    protected function expectedSimpleMerge(){
        return array(
            "value" => true,
            "key" => false
        );
    }

    protected function expectedRecursiveMerge(){
        return array(
            "value" => true,
            "key" => false,
            "test1" => array(
                "value" => 1,
                "value2" => 2
            ),
        );
    }

    protected function expectedRecursiveOverrideMerge(){
        return array(
            "value" => true,
            "key" => false,
            "test1" => array(
                "value" => "asdfasdf",
                "value2" => "jkdfjap"
            ),
        );
    }


}