<?php


namespace Voryx\ThruwayBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class ThruwayServicesPass
 * @package Voryx\ThruwayBundle\DependencyInjection\Compiler
 */
class ThruwayServicesPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {

        foreach ($container->findTaggedServiceIds('thruway.resource') as $id => $attr) {
            $className = $container->getDefinition($id)->getClass();

            $class          = new \ReflectionClass($className);
            $methods        = $class->getMethods();
            $resourceMapper = $container->getDefinition('voryx.thruway.resource.mapper');

            $resourceMapper->addMethodCall('setWorkerAnnotation', [$class->getName()]);

            foreach ($methods as $method) {
                $resourceMapper->addMethodCall('map', [$id, $class->getName(), $method->getName()]);
            }
        }
    }
}