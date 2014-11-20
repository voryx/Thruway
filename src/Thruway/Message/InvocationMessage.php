<?php

namespace Thruway\Message;

use Thruway\Common\Utils;
use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\DetailsTrait;
use Thruway\Message\Traits\RequestTrait;
use Thruway\Registration;
use Thruway\Session;

/**
 * Class InvocationMessage
 * Actual invocation of an endpoint sent by Dealer to a Callee.
 * <code>[INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict]</code>
 * <code>[INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict, CALL.Arguments|list]</code>
 * <code>[INVOCATION, Request|id, REGISTERED.Registration|id, Details|dict, CALL.Arguments|list, CALL.ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class InvocationMessage extends Message
{

    use RequestTrait;
    use DetailsTrait;
    use ArgumentsTrait;

    /**
     * @var int
     */
    private $registrationId;

    /**
     * Constructor
     *
     * @param int $requestId
     * @param int $registrationId
     * @param \stdClass $details
     * @param mixed $arguments
     * @param mixed $argumentsKw
     */
    public function __construct($requestId, $registrationId, $details, $arguments = null, $argumentsKw = null)
    {
        $this->setRequestId($requestId);
        $this->setRegistrationId($registrationId);
        $this->setDetails($details);
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

        $a = [$this->getRequestId(), $this->getRegistrationId(), $this->getDetails()];

        return array_merge($a, $this->getArgumentsForSerialization());
    }

    /**
     * Create Invocation message from Call message and registration
     *
     * @param \Thruway\Message\CallMessage $msg
     * @param \Thruway\Registration $registration
     * @return \Thruway\Message\InvocationMessage
     */
    public static function createMessageFrom(CallMessage $msg, Registration $registration)
    {
        $requestId = Utils::getUniqueId();
        $details   = new \stdClass();

        return new static($requestId, $registration->getId(), $details, $msg->getArguments(), $msg->getArgumentsKw());
    }

    /**
     * Get Registration ID
     *
     * @return int
     */
    public function getRegistrationId()
    {
        return $this->registrationId;
    }

    /**
     * Set Registration ID
     *
     * @param int $registrationId
     */
    public function setRegistrationId($registrationId)
    {
        $this->registrationId = $registrationId;
    }

}
