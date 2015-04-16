<?php
namespace Recognize\FilemanagerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder,
    Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Class Configuration
 * @package Recognize\FilemanagerBundle\DependencyInjection
 * @author Kevin te Raa <k.teraa@recognize.nl>
 */
class Configuration implements ConfigurationInterface {

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();

        // Load the default values
        $yaml = new Parser();
        $defaultconfig = $yaml->parse( file_get_contents(__DIR__.'/../Resources/config/config.yml') );

        $rootNode = $treeBuilder->root('recognize_filemanager');
        $rootNode
            ->children()
                ->scalarNode('default_directory')->defaultValue('')->end()
            ->end()
        ;

        return $treeBuilder;
    }

}