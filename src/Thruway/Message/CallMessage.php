<?php

namespace Thruway\Message;


/**
 * Class CallMessage
 * Call as originally issued by the Caller to the Dealer.
 * <code>[CALL, Request|id, Options|dict, Procedure|uri]</code>
 * <code>[CALL, Request|id, Options|dict, Procedure|uri, Arguments|list]</code>
 * <code>[CALL, Request|id, Options|dict, Procedure|uri, Arguments|list, ArgumentsKw|dict]</code>
 * 
 * @package Thruway\Message
 */
class CallMessage extends Message
{
    /**
     * using arguments trait
     * @see \Thruway\Message\ArgumentsTrait
     */
    use ArgumentsTrait;

    /**
     * @var int
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
     * Contructor
     * 
     * @param int $requestId
     * @param mixed $options
     * @param string $procedureName
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    function __construct($requestId, $options, $procedureName, $arguments = null, $argumentsKw = null)
    {
        $this->setRequestId($requestId);
        $this->setOptions($options);
        $this->setProcedureName($procedureName);
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
    }

    /**
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
        $a = [
            $this->getRequestId(),
            $this->getOptions(),
            $this->getProcedureName(),
        ];

        $a = array_merge($a, $this->getArgumentsForSerialization());

        return $a;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->options = Message::shouldBeDictionary($options);
    }

    /**
     * @return mixed
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @param mixed $procedureName
     */
    public function setProcedureName($procedureName)
    {
        $this->procedureName = $procedureName;
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

}