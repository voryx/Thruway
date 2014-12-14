<?php

namespace Voryx\ThruwayBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\KernelInterface;
use Voryx\ThruwayBundle\DependencyInjection\Compiler\AnnotationConfigurationPass;
use Voryx\ThruwayBundle\DependencyInjection\Compiler\ServiceConfigurationPass;

/**
 * Class VoryxThruwayBundle
 * @package Voryx\ThruwayBundle
 */
class VoryxThruwayBundle extends Bundle
{

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $passConfig = $container->getCompilerPassConfig();
        $passConfig->addPass(new AnnotationConfigurationPass($this->kernel));
        $passConfig->addPass(new ServiceConfigurationPass());
    }
}
