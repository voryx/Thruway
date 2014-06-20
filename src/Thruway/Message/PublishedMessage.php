<?php

namespace Thruway\Message;


class PublishedMessage extends Message {
    const MSG_CODE = Message::MSG_PUBLISHED;

    private $requestId;

    private $publicationId;

    function __construct($requestId, $publicationId)
    {
        $this->requestId = $requestId;
        $this->publicationId = $publicationId;

    }


    /**
     * @return int
     */
    public function getMsgCode() { return static::MSG_CODE; }

    /**
     * @param mixed $publicationId
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    /**
     * @return mixed
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param mixed $requestId
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
        return array($this->getRequestId(), $this->getPublicationId());
    }

}