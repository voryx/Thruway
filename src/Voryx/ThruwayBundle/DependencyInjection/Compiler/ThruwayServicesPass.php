<?php


namespace Voryx\ThruwayBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class ThruwayServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        foreach ($container->findTaggedServiceIds('thruway.resource') as $id => $attr) {
            $class = $container->getDefinition($id)->getClass();

            $class = new \ReflectionClass($class);
            $methods = $class->getMethods();

            foreach ($methods as $method) {
                $container
                    ->getDefinition('voryx.thruway.resource.mapper')
                    ->addMethodCall('map', [$id, $class->getName(), $method->getName()]);
            }
        }
    }
}