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
        $this->details = (object)$details;
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
}