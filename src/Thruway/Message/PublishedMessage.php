<?php

namespace Thruway\Message;

/**
 * Class PublishedMessage
 * Acknowledge sent by a Broker to a Publisher for acknowledged publications.
 * <code>[PUBLISHED, PUBLISH.Request|id, Publication|id]</code>
 *
 * @package Thruway\Message
 */

class PublishedMessage extends Message
{

    /**
     *
     * @var int
     */
    private $requestId;

    /**
     *
     * @var int
     */
    private $publicationId;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param int $publicationId
     */
    public function __construct($requestId, $publicationId)
    {
        $this->requestId     = $requestId;
        $this->publicationId = $publicationId;
    }

    /**
     * Get message code
     * 
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_PUBLISHED;
    }

    /**
     * Set publication ID
     * 
     * @param int $publicationId
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    /**
     * Get publication ID
     * 
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

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

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRequestId(), $this->getPublicationId()];
    }

}
