<?php

namespace Thruway\Message\Traits;

/**
 * Class DetailsTrait
 * @package Thruway\Message
 */
trait DetailsTrait
{
    /**
     * Abort message details
     *
     * @var \stdClass
     */
    private $details;


    /**
     * Set abort message details
     *
     * @param \stdClass|array $details
     */
    public function setDetails($details)
    {
        $this->details = (object) $details;
    }

    /**
     * Get abort message details
     *
     * @return \stdClass
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param $name
     * @param \stdClass $features
     */
    public function addFeatures($name, \stdClass $features)
    {
        $this->details        = isset($this->details) ? $this->details : new \stdClass();
        $this->details->roles = isset($this->details->roles) ? $this->details->roles : new \stdClass();

        $this->details->roles->$name = (object) ["features" => $features];
    }
}