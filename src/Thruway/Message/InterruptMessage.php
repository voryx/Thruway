<?php


namespace Thruway\Message;


class InterruptMessage extends Message {
    /**
     * @var int
     */
    private $requestId;
    /**
     * @var array
     */
    private $options;

    function __construct($requestId, $options)
    {
        $this->setRequestId($requestId);
        $this->setOptions($options);
    }

    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return Message::MSG_INTERRUPT;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->getRequestId(), (object)$this->getOptions()];
    }

    /**
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @param int $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }
}