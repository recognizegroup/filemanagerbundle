<?php
namespace Recognize\FilemanagerBundle\Twig;

use Symfony\Component\Routing\Router;
use Twig_Error_Runtime;
use Twig_Extension;
use Twig_SimpleFilter;

class FilemanagerExtension extends Twig_Extension {

    private $container;
    private $pathconfig;

    private $paths_generated = false;

    public function __construct( $container, $config = array() ){
        $this->container = $container;

        $this->pathconfig = array();
        if( isset( $config['api_paths'] ) ){
            $this->pathconfig = $config['api_paths'];
        }
    }

    public function getFunctions(){
        return array(
            "filemanager" => new \Twig_Function_Method($this, "renderFilemanager", array('is_safe' => array('html')))
        );
    }

    /**
     * Creates a custom twig recursive merge for the filemanager
     *
     * @return array
     */
    public function getFilters(){
        return array(
            "config_merge" =>
                new Twig_SimpleFilter("config_merge",
                function( $arr1, $arr2 ){
                if (!is_array($arr1) || !is_array($arr2)) {
                        throw new Twig_Error_Runtime(
                            sprintf('The config merge filter only works with arrays or hashes; %s and %s given.',
                                gettype($arr1), gettype($arr2))
                        );
                }
                $merge_function = new ConfigurationMerge();
                return $merge_function($arr1, $arr2);
            })
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

        // Generate the filemanager api configuration as a global variable in twig once
        if( $this->paths_generated == false ){

            $twig = $this->container->get('twig');

            /** @var Router $router */
            $router = $this->container->get('router');
            if( $twig != null && $router != null ){
                $collection = $router->getRouteCollection();

                $apiconfig = array();
                $varnames = array_keys( $this->pathconfig );
                for( $i = 0, $length = count( $varnames ); $i < $length; $i++ ) {
                    $this->pathconfig[$varnames[$i]] = $collection->get($this->pathconfig[$varnames[$i]])->getPath();
                }

                $requestcontext = $router->getGenerator()->getContext();

                $apiconfig['startingDirectory'] = $this->container->get('recognize.file_manager')->getCurrentRelativeDirectory();
                $apiconfig['url'] = $requestcontext->getScheme() . "://" . $requestcontext->getHost();
                $apiconfig['paths'] = $this->pathconfig;

                $twig->addGlobal( "filemanager_api", array("api" => $apiconfig ) );
            }

            $this->paths_generated = true;
        }

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