<?php

namespace Thruway\Message;

use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\Traits\RequestTrait;


/**
 * Class CallMessage
 * Call as originally issued by the Caller to the Dealer.
 * <code>[CALL, Request|id, Options|dict, Procedure|uri]</code>
 * <code>[CALL, Request|id, Options|dict, Procedure|uri, Arguments|list]</code>
 * <code>[CALL, Request|id, Options|dict, Procedure|uri, Arguments|list, ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class CallMessage extends Message implements ActionMessageInterface
{

    use RequestTrait;
    use OptionsTrait;
    use ArgumentsTrait;

    /**
     * @var string
     */
    private $procedureName;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param \stdClass $options
     * @param string $procedureName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    public function __construct($requestId, $options, $procedureName, $arguments = null, $argumentsKw = null)
    {
        $this->setRequestId($requestId);
        $this->setOptions($options);
        $this->setProcedureName($procedureName);
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_CALL;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        $a = [$this->getRequestId(), $this->getOptions(), $this->getProcedureName()];

        return array_merge($a, $this->getArgumentsForSerialization());

    }

    /**
     * @return mixed
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @param string $procedureName
     */
    public function setProcedureName($procedureName)
    {
        $this->procedureName = strtolower($procedureName);
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
        return "call";
    }


}
