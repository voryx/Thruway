<?php

namespace Voryx\ThruwayBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Define Worker
 *
 * How to use:
 *   '@Worker("worker_name")'
 *   '@Worker("worker_name", realm = "realm1", uri = "ws://127.0.0.1:8080")'
 *   '@Worker("worker_name", maxProcesses = 10)'
 *
 * @Annotation
 * @Target({"CLASS"})
 *
 */
class Worker implements AnnotationInterface
{
    /**
     * @Required
     * @var string
     */
    protected $value;

    protected $maxProcesses;

    protected $realm;

    protected $uri;

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
    public function getMaxProcesses()
    {
        return $this->maxProcesses;
    }

    /**
     * @return mixed
     */
    public function getRealm()
    {
        return $this->realm;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }


    /**
     * @return mixed
     */
    public function getWorker()
    {
        return $this->value;
    }
}
