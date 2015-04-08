<?php
use Recognize\FilemanagerBundle\DependencyInjection\RecognizeFilemanagerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RecognizeFilemanagerExtensionTest extends \PHPUnit_Framework_TestCase{

    /**
     * @var RecognizeFilemanagerExtension
     */
    private $extension;

    /**
     * Root name of the configuration
     *
     * @var string
     */
    private $root;

    public function setUp() {
        parent::setUp();

        $this->extension = new RecognizeFilemanagerExtension();
        $this->root = "recognize_filemanager";
    }

    public function testGetConfigWithDefaultValues() {
        $this->extension->load(array(), $container = $this->getContainer());

        $this->assertTrue($container->hasParameter($this->root . ".config"));
        $config = $container->getParameter($this->root . ".config");
    }

    public function testAlias(){
        $this->assertEquals( "recognize_filemanager", $this->extension->getAlias() );
    }


    public function getContainer(){
        $container = new ContainerBuilder();
        $container->setParameter('recognize_filemanager.config', array());
        return $container;
    }
}