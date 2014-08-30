<?php


namespace Voryx\ThruwayBundle;


use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Config\Definition\Exception\Exception;
use Voryx\ThruwayBundle\Mapping\URIClassMapping;

/**
 * Class ResourceMapper
 * @package Voryx\ThruwayBundle
 */
class ResourceMapper
{

    const RPC_ANNOTATION_CLASS = 'Voryx\\ThruwayBundle\\Annotation\\RPC';

    const SUBSCRIBE_ANNOTATION_CLASS = 'Voryx\\ThruwayBundle\\Annotation\\Subscribe';


    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var array
     */
    private $mappings = [];


    function __construct(Reader $reader)
    {

        $this->reader = $reader;
    }


    public function map($serviceId, $class, $method)
    {

        $class = new \ReflectionClass($class);
        $method = $class->getMethod($method);

        $annotations = [];
        $annotations[] = $this->reader->getMethodAnnotation($method, self::RPC_ANNOTATION_CLASS);
        $annotations[] = $this->reader->getMethodAnnotation($method, self::SUBSCRIBE_ANNOTATION_CLASS);

        foreach ($annotations as $annotation) {
            if ($annotation) {
                $mapping = new URIClassMapping($serviceId, $method, $annotation);

                if (isset($this->mappings[$annotation->getName()])) {
                    $uri = $annotation->getName();
                    $className = $this->mappings[$annotation->getName()]->getMethod()->class;

                    throw new Exception("The URI '{$uri}' has already been registered in {$className}");
                }

                $this->mappings[$annotation->getName()] = $mapping;
            }
        }

    }

    /**
     * @return mixed
     */
    public function getMappings()
    {
        return $this->mappings;
    }

    /**
     * @param mixed $mappings
     */
    public function setMappings($mappings)
    {
        $this->mappings = $mappings;
    }

    /**
     * @param $mapping
     *
     */
    public function addMapping($mapping)
    {
        $this->mappings[] = $mapping;
    }

    /**
     * @return Reader
     */
    public function getReader()
    {
        return $this->reader;
    }

    /**
     * @param Reader $reader
     */
    public function setReader($reader)
    {
        $this->reader = $reader;
    }

} 