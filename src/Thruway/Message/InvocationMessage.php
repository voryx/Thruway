<?php

namespace Thruway\Message;


use Thruway\Registration;
use Thruway\Session;

/**
 * Class InvocationMessage
 * @package Thruway\Message
 */
class InvocationMessage extends Message
{

    /**
     * @var
     */
    private $requestId;

    /**
     * @var
     */
    private $registrationId;

    /**
     * @var
     */
    private $details;

    /**
     * @var null
     */
    private $arguments;

    /**
     * @var null
     */
    private $argumentsKw;

    /**
     * @param $requestId
     * @param $registrationId
     * @param $details
     * @param null $arguments
     * @param null $argumentsKw
     */
    function __construct($requestId, $registrationId, $details, $arguments = null, $argumentsKw = null)
    {
        $this->requestId = $requestId;
        $this->registrationId = $registrationId;
        $this->details = $details;
        $this->arguments = $arguments ? $arguments : (array)array();
        $this->argumentsKw = $argumentsKw ? $argumentsKw : new \stdClass();

    }


    /**
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_INVOCATION;
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
            $this->requestId,
            $this->registrationId,
            $this->details
        );

        $a = array_merge($a, array($this->getArguments()));
        if ($this->getArgumentsKw() != null) {
            $a = array_merge($a, array($this->getArgumentsKw()));
        }

        return $a;
    }

    /**
     * @param CallMessage $msg
     * @param Registration $registration
     * @return static
     */
    static function createMessageFrom(CallMessage $msg, Registration $registration)
    {
        $requestId = Session::getUniqueId();
        $details = new \stdClass();

        return new static($requestId, $registration->getId(), $details, $msg->getArguments(), $msg->getArgumentsKw());
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

    /**
     * @return mixed
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param mixed $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return mixed
     */
    public function getArgumentsKw()
    {
        return $this->argumentsKw;
    }

    /**
     * @param mixed $argumentsKw
     */
    public function setArgumentsKw($argumentsKw)
    {
        $this->argumentsKw = $argumentsKw;
    }

    /**
     * @return mixed
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param mixed $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * @return mixed
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * @param mixed $registrationId
     */
    public function setRegistrationId($registrationId)
    {
        $this->registrationId = $registrationId;
    }


}
