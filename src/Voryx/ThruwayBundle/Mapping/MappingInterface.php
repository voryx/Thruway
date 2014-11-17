<?php

namespace Voryx\ThruwayBundle\Mapping;

use Voryx\ThruwayBundle\Annotation\AnnotationInterface;

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
     * @param AnnotationInterface $annotation
     * @return mixed
     */
    public function setAnnotation(AnnotationInterface $annotation);

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
