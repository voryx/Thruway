<?php

namespace Thruway\Message;


/**
 * Class CallMessage
 * @package Thruway\Message
 */
class CallMessage extends Message
{
    use ArgumentsTrait;

    /**
     * @var
     */
    private $requestId;

    /**
     * @var
     */
    private $options;

    /**
     * @var
     */
    private $procedureName;



    /**
     * @param $requestId
     * @param $options
     * @param $procedureName
     * @param $arguments
     * @param $argumentsKw
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
        $a = array(
            $this->getRequestId(),
            $this->getOptions(),
            $this->getProcedureName(),
        );

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