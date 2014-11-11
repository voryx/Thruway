<?php

namespace Thruway\Message\Traits;


/**
 * Class RequestTrait
 * @package Thruway\Message\Traits
 */
trait RequestTrait
{

    /**
     * @var int
     */
    private $requestId;

    /**
     * Get request ID
     *
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set request ID
     *
     * @param int $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

}