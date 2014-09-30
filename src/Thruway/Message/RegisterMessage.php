<?php

namespace Thruway\Message;

/**
 * Class RegisterMessage
 * A Callees request to register an endpoint at a Dealer.
 * <code>[REGISTER, Request|id, Options|dict, Procedure|uri]</code>
 * 
 * @package Thruway\Message
 */
class RegisterMessage extends Message
{

    /**
     * @var mixed
     */
    private $requestId;

    /**
     * @var mixed
     */
    private $options;

    /**
     * @var string
     */
    private $procedureName;

    /**
     * @param $requestId
     * @param $options
     * @param $procedureName
     */
    function __construct($requestId, $options, $procedureName)
    {
        $this->options       = $options;
        $this->procedureName = $procedureName;
        $this->requestId     = $requestId;
    }

    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_REGISTER;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        return [$this->requestId, $this->getOptions(), $this->getProcedureName()];
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return mixed
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @return mixed
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

}
