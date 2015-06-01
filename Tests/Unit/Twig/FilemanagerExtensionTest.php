<?php
namespace Recognize\FilemanagerBundle\Twig;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FilemanagerExtensionTest extends WebTestCase {

    /** @var FilemanagerExtension */
    private $extension;

    public function setUp(){
        parent::setUp();
        $client = static::createClient();

        $this->extension = new FilemanagerExtension( $client->getContainer() );
    }

    public function testIfFilemanagerAccessibleFromTwig(){
        $this->assertEquals("recognize.filemanager.twig.filemanager_extension", $this->extension->getName() );
        $this->assertEquals(array(
            "filemanager" => new \Twig_Function_Method($this->extension, "renderFilemanager", array('is_safe' => array('html')))
        ), $this->extension->getFunctions() );
    }

    public function testFilemanagerRendering(){
        $id = "teststringasdf12341234";
        $rendered_html = $this->extension->renderFilemanager( $id, array("testvariable" => "test") );

        $config = '"testvariable":"test"';
        $this->assertTrue( strpos($rendered_html, $id) !== false , "Id variable wasn't properly set in the filemanager twig html" );
        $this->assertTrue( strpos($rendered_html, $config) !== false , "Config variable wasn't properly set in the filemanager twig html" );
    }

    public function testFilemanagerThemeRendering(){
        $id = "teststringasdf12341234";
        $rendered_html = $this->extension->renderFilemanager( $id, array("testvariable" => "test"), "bootstrap" );

        $config = '"testvariable":"test"';
        $this->assertTrue( strpos($rendered_html, $id) !== false , "Id variable wasn't properly set in the filemanager bootstrap twig html" );
        $this->assertTrue( strpos($rendered_html, $config) !== false , "Config variable wasn't properly set in the filemanager bootstrap twig html" );
    }

    /**
     * @expectedException \Twig_Error_Runtime
     */
    public function testFaultyRendering(){
        $id = "teststringasdf12341234";
        $rendered_html = $this->extension->renderFilemanager( "", array("testvariable" => "test") );
    }
}