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
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $this->validate($config);

        $container->setParameter('voryx_thruway', $config);

        $this->createResourceServices($config, $container);

        $this->configureOptions($config, $container);
    }

    /**
     * Validation for config
     * @param $config
     */
    protected function validate($config)
    {

        //@todo add more config validation

        if (isset($config['resources']) && !is_array($config['resources'])) {
            throw new \InvalidArgumentException(
                'The "resources" option must be an array'
            );
        }

        if (!isset($config['uri'])) {
            throw new \InvalidArgumentException(
                'The "uri" option must be set within voryx_thruway'
            );
        }

        if (!isset($config['realm'])) {
            throw new \InvalidArgumentException(
                'The "realm" option must be set within voryx_thruway'
            );
        }
    }


    /**
     * Create a service for each resource that's configured
     *
     * @param $config
     * @param ContainerBuilder $container
     */
    protected function createResourceServices(&$config, ContainerBuilder $container)
    {
        //Create services for any of the resource classes
        foreach ($config['resources'] as $class) {
            $class      = new \ReflectionClass($class);
            $serviceId  = strtolower(str_replace("\\", "_", $class->getName()));
            $definition = new Definition($class->getName());
            $definition->addTag('thruway.resource');

            if ($class->implementsInterface('Symfony\Component\DependencyInjection\ContainerAwareInterface')) {
                $container->setDefinition($serviceId, $definition)
                    ->addMethodCall('setContainer', [new Reference('service_container')]);
            } else {
                $container->setDefinition($serviceId, $definition);
            }
        }
    }

    /**
     * Configure optional settings
     *
     * @param $config
     * @param ContainerBuilder $container
     */
    protected function configureOptions(&$config, ContainerBuilder $container)
    {
        //Add optional Manager
        if ($config['router'] && $config['router']['enable_manager'] === true) {

            //Replace the dummy manager with the client manager
            $container
                ->getDefinition('voryx.thruway.manager.client')
                ->setClass('Thruway\Manager\ManagerClient');

            //Inject the manager client into the router
            $container
                ->getDefinition('voryx.thruway.server')
                ->addMethodCall('addTransportProvider', [new Reference('voryx.thruway.internal.manager')]);

        }

        if ($config['enable_logging'] === true) {
            $container
                ->getDefinition('voryx.thruway.manager.client')
                ->addMethodCall('setLogger', [new Reference('logger')]);
        }

        if ($config['router'] && isset($config['router']['authentication']) && $config['router']['authentication'] == "in_memory") {

            //Inject the authentication manager into the router
            $container
                ->getDefinition('voryx.thruway.server')
                ->addMethodCall('setAuthenticationManager', [new Reference('voryx.thruway.authentication.manager')])
                ->addMethodCall('addTransportProvider', [new Reference('voryx.thruway.auth.manager.transport.provider')])
                ->addMethodCall('addTransportProvider', [new Reference('voryx.thruway.wamp.cra.auth.transport.provider')]);
        }

        if ($container->hasDefinition('security.user.provider.concrete.in_memory')) {
            $container->addAliases(['in_memory_user_provider' => 'security.user.provider.concrete.in_memory']);
        }
    }
}
