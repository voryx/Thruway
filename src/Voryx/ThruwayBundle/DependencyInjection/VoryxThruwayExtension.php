<?php

namespace Voryx\ThruwayBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class VoryxThruwayExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        if (isset($config['resources']) && !is_array($config['resources'])) {
            throw new \InvalidArgumentException(
                'The "resources" option must be an array'
            );
        }

        if (!isset($config['server'])) {
            throw new \InvalidArgumentException(
                'The "server" option must be set within voryx_thruway'
            );
        }

        if (!isset($config['realm'])) {
            throw new \InvalidArgumentException(
                'The "realm" option must be set within voryx_thruway'
            );
        }

        if (!isset($config['local_server'])) {
            throw new \InvalidArgumentException(
                'The "local_server" option must be set within voryx_thruway'
            );
        }

        if (!isset($config['php_path'])) {
            throw new \InvalidArgumentException(
                'The "php_path" option must be set within voryx_thruway'
            );
        }

        $container->setParameter('voryx_thruway', $config);

        //Create services for any of the resource classes
        foreach ($config['resources'] as $class) {
            $class = new \ReflectionClass($class);
            $serviceId = strtolower(str_replace("\\", "_", $class->getName()));
            $definition = new Definition($class->getName());
            $definition->addTag('thruway.resource');

            if ($class->hasMethod('setContainer')) {
                $container->setDefinition($serviceId, $definition)
                    ->addMethodCall('setContainer', [new Reference('service_container')]);
            } else {
                $container->setDefinition($serviceId, $definition);
            }
        }

        //Add optional Manager
        if ($config['enable_manager'] === true) {

            //Inject the manager into the router
            $container
                ->getDefinition('voryx.thruway.server')
                ->addArgument(new Reference('voryx.thruway.manager.client'))
                ->addMethodCall('addTransportProvider', [new Reference('voryx.thruway.internal.manager')]);
        }

        if ($config['enable_web_push'] === true) {
            //Inject the web push client into the router
            $container
                ->getDefinition('voryx.thruway.server')
                ->addMethodCall('addTransportProvider', [new Reference('voryx.thruway.internal.web.push')]);
        }

    }
}
