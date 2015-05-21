<?php

namespace Voryx\ThruwayBundle\Mapping;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Voryx\ThruwayBundle\Annotation\AnnotationInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class URIClassMapping
 * @package Voryx\ThruwayBundle\Mapping
 */
class URIClassMapping implements MappingInterface
{
    /**
     * @var AnnotationInterface
     */
    protected $annotation;

    /**
     * @var Security
     */
    protected $securityAnnotation;

    /**
     * @var \ReflectionMethod
     */
    protected $method;

    /**
     * @var
     */
    protected $serviceId;

    /**
     * @var bool
     */
    protected $active = false;

    /**
     * @param null $serviceId
     * @param \ReflectionMethod $method
     * @param AnnotationInterface $annotation
     */
    function __construct($serviceId = null, \ReflectionMethod $method = null, AnnotationInterface $annotation = null)
    {
        $this->serviceId  = $serviceId;
        $this->method     = $method;
        $this->annotation = $annotation;
    }

    /**
     * @return AnnotationInterface
     */
    public function getAnnotation()
    {
        return $this->annotation;
    }

    /**
     * @param mixed $annotation
     * @return mixed|void
     */
    public function setAnnotation(AnnotationInterface $annotation)
    {
        $this->annotation = $annotation;
    }

    /**
     * @return \ReflectionMethod
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     * @return mixed|void
     */
    public function setMethod(\ReflectionMethod $method)
    {
        if (!$method->isPublic()) {
            throw new Exception("You can not use the Register or Subscribe annotation on a non-public method");
        }
        $this->method = $method;
    }

    /**
     * @return boolean|void
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param boolean $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function getServiceId()
    {
        return $this->serviceId;
    }

    /**
     * @param mixed $serviceId
     */
    public function setServiceId($serviceId)
    {
        $this->serviceId = $serviceId;
    }

    /**
     * @return Security
     */
    public function getSecurityAnnotation()
    {
        return $this->securityAnnotation;
    }

    /**
     * @param Security $securityAnnotation
     */
    public function setSecurityAnnotation($securityAnnotation)
    {
        $this->securityAnnotation = $securityAnnotation;
    }


}
