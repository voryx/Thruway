<?php

namespace Thruway\Message;

use Thruway\Message\Traits\RequestTrait;

/**
 * Class PublishedMessage
 * Acknowledge sent by a Broker to a Publisher for acknowledged publications.
 * <code>[PUBLISHED, PUBLISH.Request|id, Publication|id]</code>
 *
 * @package Thruway\Message
 */
class PublishedMessage extends Message
{

    use RequestTrait;

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
        $this->setRequestId($requestId);
        $this->setPublicationId($publicationId);
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
