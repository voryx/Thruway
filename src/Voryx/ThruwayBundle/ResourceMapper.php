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

    const REGISTER_ANNOTATION_CLASS = 'Voryx\\ThruwayBundle\\Annotation\\Register';

    const SUBSCRIBE_ANNOTATION_CLASS = 'Voryx\\ThruwayBundle\\Annotation\\Subscribe';

    const WORKER_ANNOTATION_CLASS = 'Voryx\\ThruwayBundle\\Annotation\\Worker';

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var array
     */
    protected $mappings = [];

    /**
     * @var array
     */
    protected $workerAnnotations = [];

    /**
     * @var array
     */
    protected $workerAnnotationsClasses = [];


    /**
     * @param Reader $reader
     */
    function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }


    /**
     * @param $serviceId
     * @param $class
     * @param $method
     */
    public function map($serviceId, $class, $method)
    {

        $class         = new \ReflectionClass($class);
        $method        = $class->getMethod($method);
        $annotations   = [];
        $annotations[] = $this->reader->getMethodAnnotation($method, self::REGISTER_ANNOTATION_CLASS);
        $annotations[] = $this->reader->getMethodAnnotation($method, self::SUBSCRIBE_ANNOTATION_CLASS);

        foreach ($annotations as $annotation) {
            if ($annotation) {

                $workerAnnotation = isset($this->workerAnnotationsClasses[$class->getName()]) ? $this->workerAnnotationsClasses[$class->getName()] : null;

                if ($workerAnnotation) {
                    $worker = $workerAnnotation->getName();
                } else {
                    $worker = $annotation->getWorker() ?: "default";
                }

                $mapping = new URIClassMapping($serviceId, $method, $annotation);

                if (isset($this->mappings[$worker][$annotation->getName()])) {
                    $uri       = $annotation->getName();
                    $className = $this->mappings[$worker][$annotation->getName()]->getMethod()->class;

                    throw new Exception("The URI '{$uri}' has already been registered in '{$className}' for the worker '{$worker}'");
                }

                $this->mappings[$worker][$annotation->getName()] = $mapping;
            }
        }

    }


    /**
     * @param $class
     */
    public function setWorkerAnnotation($class)
    {

        $class      = new \ReflectionClass($class);
        $annotation = $this->reader->getClassAnnotation($class, self::WORKER_ANNOTATION_CLASS);

        if ($annotation) {

            $worker = $annotation->getWorker() ? $annotation->getWorker() : "default";

            if (isset($this->workerAnnotations[$worker])) {
                throw new Exception("The Worker '{$worker}' has already been registered in '{$class->getName()}'.  Workers can only be defined once on the Class level");
            }

            $this->workerAnnotations[$worker]                  = $annotation;
            $this->workerAnnotationsClasses[$class->getName()] = $annotation;
        }

    }

    /**
     * @param null|string $worker
     * @return mixed
     */
    public function getMappings($worker = null)
    {
        if ($worker && isset($this->mappings[$worker])) {
            return $this->mappings[$worker];
        }

        $mappings = [];
        foreach ($this->mappings as $mapping) {
            $mappings = array_merge($mappings, $mapping);
        }
        return $mappings;
    }

    /**
     * @return array
     */
    public
    function getAllMappings()
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
     * @param $uri
     * @return int|null|string
     */
    public function findWorker($uri)
    {
        $workerName = null;

        /* @var $mapping URIClassMapping */
        foreach ($this->getAllMappings() as $wn => $mappings) {
            foreach ($mappings as $key => $mapping) {
                if (strtolower($key) == strtolower($uri)) {
                    $workerName = $wn;
                }
            }

        }

        return $workerName;
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

    /**
     * @param $workerName
     * @return \Voryx\ThruwayBundle\Annotation\Worker
     */
    public function getWorkerAnnotation($workerName)
    {
        return isset($this->workerAnnotations[$workerName]) ? $this->workerAnnotations[$workerName] : false;
    }

} 