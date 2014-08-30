<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 8/28/14
 * Time: 10:37 PM
 */

namespace Voryx\ThruwayBundle\Annotation;

use Doctrine\ORM\Mapping\Annotation;


/**
 * WAMP Subscribe
 *
 * How to use:
 *   '@Subscribe("com.example.subscribe1")'
 *
 * @Annotation
 * @Target({"METHOD"})
 *
 */
class Subscribe implements Annotation
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
                throw new \InvalidArgumentException(sprintf('Property "%s" does not exist for the Subscribe Annotation', $key));
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

