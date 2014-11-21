<?php

namespace Thruway\Message;

use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\Traits\RequestTrait;

/**
 * Class CancelMessage
 * A Caller can cancel and issued call actively by sending a cancel message to the Dealer.
 * <code>[CANCEL, CALL.Request|id, Options|dict]</code>
 *
 * @package Thruway\Message
 */
class CancelMessage extends Message
{

    use RequestTrait;
    use OptionsTrait;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param \stdClass $options
     */
    public function __construct($requestId, $options)
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
        return static::MSG_CANCEL;
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
