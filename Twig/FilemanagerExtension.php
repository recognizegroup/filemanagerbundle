<?php
namespace Recognize\FilemanagerBundle\Twig;

use Twig_Extension;

class FilemanagerExtension extends Twig_Extension {

    private $container;

    public function __construct( $container ){
        $this->container = $container;
    }

    public function getFunctions(){
        return array(
            "filemanager" => new \Twig_Function_Method($this, "renderFilemanager", array('is_safe' => array('html')))
        );
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName() {
        return "recognize.filemanager.twig.filemanager_extension";
    }

    /**
     * Renders a C3 chart using the twig variables
     *
     * @param string $name
     * @param null $config
     * @return mixed
     * @throws \Twig_Error_Runtime
     */
    public function renderFilemanager($id = "", $config = null, $theme = "") {
        if( $id == "" ){
            throw new \Twig_Error_Runtime( 'You must supply a unique id for the filemanager function ( filemanager("123") )' );
        }

        if( strlen( $theme ) > 0 ){
            $theme = "." . $theme;
        }

        return $this->container->get('templating')
            ->render(
                "RecognizeFilemanagerBundle::base" . $theme . ".html.twig", array("id" => $id, "filemanager_config" => $config)
            );

    }
}