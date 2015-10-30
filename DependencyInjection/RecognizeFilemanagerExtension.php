<?php
namespace Recognize\FilemanagerBundle\DependencyInjection;

use Recognize\FilemanagerBundle\Security\NoConnectionAclProvider;
use Recognize\FilemanagerBundle\Service\FileACLManagerService;
use Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\Config\FileLocator;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

/**
 * Class Recognize\WysiwygBundle\RecognizeWysiwygExtension
 * @package Recognize\WysiwygBundle\DependencyInjection
 * @author Kevin te Raa <k.teraa@recognize.nl>
 */
class RecognizeFilemanagerExtension extends Extension {

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, ContainerBuilder $container) {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('recognize_filemanager.config', $config);

        // Make sure the ACL Manager service is initialized even if the acls aren't enabled
        if( $config['security'] !== "enabled" ){
            $container->setDefinition(
                "security.acl.provider",
                new Definition(
                    "Recognize\FilemanagerBundle\Security\NoConnectionAclProvider"
                )
            );
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * @return string
     */
    public function getAlias() {
        return 'recognize_filemanager';
    }

}
