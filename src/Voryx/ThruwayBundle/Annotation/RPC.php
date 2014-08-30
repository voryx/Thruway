<?php

namespace Voryx\ThruwayBundle\Annotation;

use Doctrine\ORM\Mapping\Annotation;

/**
 * Register WAMP RPC call
 *
 * How to use:
 *   '@RPC("com.example.procedure1")'
 *
 * @Annotation
 * @Target({"METHOD"})
 *
 */
class RPC implements Annotation
{
    /**
     * @Required
     * @var string
     */
    protected $value;

    protected $serializerGroups;

    protected $serializerEnableMaxDepthChecks;

    /**
     * @param $options
     * @throws \InvalidArgumentException
     */
    public function __construct($options)
    {

        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(
                    sprintf('Property "%s" does not exist for the RPC annotation', $key)
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


}