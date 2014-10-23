<?php

namespace Thruway\Message;

/**
 * Class RegisterMessage
 * A Callees request to register an endpoint at a Dealer.
 * <code>[REGISTER, Request|id, Options|dict, Procedure|uri]</code>
 *
 * @package Thruway\Message
 */
class RegisterMessage extends Message implements ActionMessageInterface
{

    /**
     * @var int
     */
    private $requestId;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $procedureName;

    /**
     * @param int $requestId
     * @param array $options
     * @param string $procedureName
     */
    public function __construct($requestId, $options, $procedureName)
    {
        $this->setOptions($options);
        $this->procedureName = strtolower($procedureName);
        $this->requestId     = $requestId;
    }

    /**
     * Get message code
     * 
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
     * Get options
     * 
     * @return array
     */
    public function getOptions()
    {
        return (array)$this->options;
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
     * Get procedure name
     * 
     * @return string
     */
    public function getProcedureName()
    {
        return $this->procedureName;
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
     * This returns the Uri so that the authorization manager doesn't have to know
     * exactly the type of object to get the Uri
     *
     * @return mixed
     */
    public function getUri()
    {
        return $this->getProcedureName();
    }

    /**
     * This returns the action name "publish", "subscribe", "register", "call"
     *
     * @return mixed
     */
    public function getActionName()
    {
        return "register";
    }


}
