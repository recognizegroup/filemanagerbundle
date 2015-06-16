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
        $actiondefaults = $defaultconfig['recognize_filemanager']['security']['actions'];

        $rootNode = $treeBuilder->root('recognize_filemanager');
        $rootNode
            ->children()
                ->arrayNode('directories')
                    ->useAttributeAsKey('name')
                    ->requiresAtLeastOneElement()
                    ->prototype('scalar')->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('thumbnail')
                    ->children()
                        ->scalarNode('directory')->defaultValue('')->end()
                        ->scalarNode('size')->defaultValue('')->end()
                    ->end()
                ->end()
                ->arrayNode('api_paths')
                    ->children()
                        ->scalarNode('read')->defaultValue('')->end()
                        ->scalarNode('search')->defaultValue('')->end()
                        ->scalarNode('create')->defaultValue('')->end()
                        ->scalarNode('upload')->defaultValue('')->end()
                        ->scalarNode('move')->defaultValue('')->end()
                        ->scalarNode('rename')->defaultValue('')->end()
                        ->scalarNode('delete')->defaultValue('')->end()
                        ->scalarNode('download')->defaultValue('')->end()
                        ->scalarNode('preview')->defaultValue('')->end()
                    ->end()
                ->end()
                ->arrayNode('security')
                    ->children()
                        ->arrayNode('actions')
                            ->children()
                                ->arrayNode('open')->prototype('scalar')->defaultValue( $actiondefaults['open'] )->end()->end()
                                ->arrayNode('upload')->prototype('scalar')->defaultValue( $actiondefaults['upload'] )->end()->end()
                                ->arrayNode('create')->prototype('scalar')->defaultValue( $actiondefaults['create'] )->end()->end()
                                ->arrayNode('rename')->prototype('scalar')->defaultValue( $actiondefaults['rename'] )->end()->end()
                                ->arrayNode('move')->prototype('scalar')->defaultValue( $actiondefaults['move'] )->end()->end()
                                ->arrayNode('delete')->prototype('scalar')->defaultValue( $actiondefaults['delete'] )->end()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}