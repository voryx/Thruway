<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/12/14
 * Time: 10:57 PM
 */

namespace AutobahnPHP\Message;


use AutobahnPHP\Registration;
use AutobahnPHP\Session;

class InvocationMessage extends Message
{

    private $requestId;

    private $registrationId;

    private $details;

    private $arguments;

    private $argumentsKw;

    function __construct($requestId, $registrationId, $details, $arguments, $argumentsKw)
    {
        $this->requestId = $requestId;
        $this->registrationId = $registrationId;
        $this->details = $details;
        $this->arguments = $arguments;
        $this->argumentsKw = $argumentsKw;

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

        if ($this->getArguments() != null) {
            $a = array_merge($a, array($this->getArguments()));
            if ($this->getArgumentsKw() != null) {
                $a = array_merge($a, array($this->getArgumentsKw()));
            }
        }

        return $a;
    }

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