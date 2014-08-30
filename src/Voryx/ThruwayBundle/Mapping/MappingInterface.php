<?php
namespace Voryx\ThruwayBundle\Mapping;

use Doctrine\ORM\Mapping\Annotation;

/**
 * Interface MappingInterface
 * @package Voryx\ThruwayBundle\Mapping
 */
interface MappingInterface
{

    /**
     * @return mixed
     */
    public function getAnnotation();

    /**
     * @param Annotation $annotation
     * @return mixed
     */
    public function setAnnotation(Annotation $annotation);

    /**
     * @return mixed
     */
    public function getMethod();

    /**
     * @param \ReflectionMethod $method
     * @return mixed
     */
    public function setMethod(\ReflectionMethod $method);

    /**
     * @return mixed
     */
    public function getServiceId();

    /**
     * @param mixed $serviceId
     */
    public function setServiceId($serviceId);
} 