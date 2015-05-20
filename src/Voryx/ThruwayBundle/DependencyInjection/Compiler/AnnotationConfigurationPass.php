<?php

namespace Voryx\ThruwayBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;


/**
 * Class AnnotationConfigurationPass
 * @package Voryx\ThruwayBundle\DependencyInjection\Compiler
 */
class AnnotationConfigurationPass implements CompilerPassInterface
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
     * Create services for any of the classes that have Thruway Annotations
     *
     * @param ContainerBuilder $container
     *
     * @api
     */
    public function process(ContainerBuilder $container)
    {

        $config      = $container->getParameter('voryx_thruway');
        $bundleFiles = $this->getBundleFiles($container);
        $files       = $config['locations']['files'] + $bundleFiles;

        foreach ($files as $class) {
            $class      = new \ReflectionClass($class);
            $serviceId  = strtolower(str_replace("\\", "_", $class->getName()));
            $definition = new Definition($class->getName());
            $definition->addTag('thruway.resource');

            if ($class->implementsInterface('Symfony\Component\DependencyInjection\ContainerAwareInterface')) {
                $container->setDefinition($serviceId, $definition)
                    ->addMethodCall('setContainer', [new Reference('thruway_container')]);
            } else {
                $container->setDefinition($serviceId, $definition);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @return array
     * @throws \Exception
     */
    private function getBundleFiles(ContainerBuilder $container)
    {

        $config      = $container->getParameter('voryx_thruway');
        $scanBundles = $config['locations']['bundles'];
        $bundles     = $this->kernel->getBundles();
        $files       = [];

        foreach ($bundles as $name => $bundle) {
            if (!in_array($name, $scanBundles, true)) {
                continue;
            }

            $finder = new Finder();
            $finder->files()->in($bundle->getPath())->name('*.php')->contains('Voryx\ThruwayBundle\Annotation')->depth('< 5');

            /* @var $file \Symfony\Component\Finder\SplFileInfo */
            foreach ($finder as $file) {
                $files[] = $this->getClassName($file->getPath() . DIRECTORY_SEPARATOR . $file->getFilename());
            }

        }

        return $files;
    }

    /**
     * Only supports one namespaced class per file
     * Original method copied from https://github.com/schmittjoh/JMSDiExtraBundle/blob/master/DependencyInjection/Compiler/AnnotationConfigurationPass.php#L108
     *
     * @param string $filename
     * @return string if the class name cannot be extracted
     * @throws \Exception
     */
    private function getClassName($filename)
    {
        $src = file_get_contents($filename);
        if (!preg_match('/\bnamespace\s+([^;]+);/s', $src, $match)) {
            throw new \Exception(sprintf('Namespace could not be determined for file "%s".', $filename));
        }
        $namespace = $match[1];
        if (!preg_match('/\bclass\s+([^\s]+)\s+(?:extends|implements|{)/is', $src, $match)) {
            throw new \Exception(sprintf('Could not extract class name from file "%s".', $filename));
        }
        return $namespace . '\\' . $match[1];
    }
}