<?php

namespace Thruway\Message;

use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\Traits\RequestTrait;

/**
 * Class RegisterMessage
 * A Callees request to register an endpoint at a Dealer.
 * <code>[REGISTER, Request|id, Options|dict, Procedure|uri]</code>
 *
 * @package Thruway\Message
 */
class RegisterMessage extends Message implements ActionMessageInterface
{
    use RequestTrait;
    use OptionsTrait;

    /**
     * @var string
     */
    private $procedureName;

    /**
     * @param int $requestId
     * @param \stdClass $options
     * @param string $procedureName
     */
    public function __construct($requestId, $options, $procedureName)
    {
        $this->setOptions($options);
        $this->setProcedureName(strtolower($procedureName));
        $this->setRequestId($requestId);
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
        return [$this->requestId, (object)$this->getOptions(), $this->getProcedureName()];
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

    /**
     * @param string $procedureName
     */
    public function setProcedureName($procedureName)
    {
        $this->procedureName = $procedureName;
    }

}
