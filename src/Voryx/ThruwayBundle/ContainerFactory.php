<?php


namespace Voryx\ThruwayBundle;


use Symfony\Component\HttpKernel\Kernel;

class ContainerFactory
{

    public static function createContainer(Kernel $kernel)
    {
        $container = $kernel->getName() . ucfirst($kernel->getEnvironment()) . ($kernel->isDebug() ? 'Debug' : '') . 'ProjectContainer';
        return new $container();
    }

}