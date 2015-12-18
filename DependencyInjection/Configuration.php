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
        $access_control_default = $defaultconfig['recognize_filemanager']['access_control'];

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
                        ->scalarNode('strategy')->defaultValue("all")->end()
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
                ->scalarNode('security')->defaultValue("disabled")->end()
                ->arrayNode('access_control')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('directory')
                                ->defaultValue("default")
                            ->end()
                            ->scalarNode('path')
                                ->defaultNull()
                                ->example('^/path to resource from all working directories/')
                            ->end()
                            ->arrayNode('actions')
                                ->prototype('scalar')
                                    ->validate()
                                        ->ifNotInArray(array("open", "upload", "create", "rename", "move", "delete"))
                                        ->thenInvalid( "Invalid action - Can only use open, upload, create, rename, move, rename and delete " )
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('roles')
                                ->isRequired()
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;


    }

}