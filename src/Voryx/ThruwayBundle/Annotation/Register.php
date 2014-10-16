<?php

namespace Voryx\ThruwayBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\ORM\Mapping\Annotation;

/**
 * Register WAMP RPC call
 *
 * How to use:
 *   '@Register("com.example.procedure1")'
 *
 * @Annotation
 * @Target({"METHOD"})
 *
 */
class Register implements Annotation
{
    /**
     * @Required
     * @var string
     */
    protected $value;

    protected $serializerGroups;

    protected $serializerEnableMaxDepthChecks;

    protected $worker;

    protected $multiRegister;

    protected $discloseCaller;

    /**
     * @param $options
     * @throws \InvalidArgumentException
     */
    public function __construct($options)
    {

        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(
                    sprintf('Property "%s" does not exist for the Register annotation', $key)
                );
            }
            $this->$key = $value;
        }

    }


    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getSerializerEnableMaxDepthChecks()
    {
        return $this->serializerEnableMaxDepthChecks;
    }

    /**
     * @return mixed
     */
    public function getSerializerGroups()
    {
        return $this->serializerGroups;
    }

    /**
     * @return mixed
     */
    public function getWorker()
    {
        return $this->worker;
    }

    /**
     * @return mixed
     */
    public function getMultiRegister()
    {
        return $this->multiRegister;
    }

    /**
     * @return mixed
     */
    public function getDiscloseCaller()
    {
        return $this->discloseCaller;
    }


}