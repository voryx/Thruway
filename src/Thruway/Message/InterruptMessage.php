<?php


namespace Thruway\Message;


use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\Traits\RequestTrait;

class InterruptMessage extends Message
{

    use RequestTrait;
    use OptionsTrait;

    /**
     * @param int $requestId
     * @param \stdClass $options
     */
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

}