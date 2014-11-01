<?php

namespace Thruway\Message;

/**
 * Class CancelMessage
 * A Caller can cancel and issued call actively by sending a cancel message to the Dealer.
 * <code>[CANCEL, CALL.Request|id, Options|dict]</code>
 *
 * @package Thruway\Message
 */
class CancelMessage extends Message
{

    /**
     *
     * @var int
     */
    public $requestId;

    /**
     *
     * @var array
     */
    public $options;

    /**
     * Constructor
     * 
     * @param int $requestId
     * @param array $options
     */
    public function __construct($requestId, $options)
    {
        parent::__construct();

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
     * Get request ID
     * 
     * @return int
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set options
     * 
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;   
    }

    /**
     * Get options
     * 
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

}
