<?php

namespace Voryx\ThruwayBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Voryx\ThruwayBundle\DependencyInjection\Compiler\ThruwayServicesPass;

class VoryxThruwayBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $passConfig = $container->getCompilerPassConfig();
        $passConfig->addPass(new ThruwayServicesPass());
    }
}
