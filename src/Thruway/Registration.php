<?php

namespace Thruway;

use Thruway\Message\CallMessage;
use Thruway\Message\InvocationMessage;
use Thruway\Message\RegisterMessage;


/**
 * Class Registration
 *
 * @package Thruway
 */
class Registration
{

    /**
     * @var mixed
     */
    private $id;

    /**
     * @var \Thruway\Session
     */
    private $session;

    /**
     * @var string
     */
    private $procedureName;

    /**
     * @var bool
     */
    private $discloseCaller;

    /**
     * @var bool
     */
    private $allowMultipleRegistrations;

    /**
     * @var array
     */
    private $calls;

    /**
     * This holds the count of total invocations
     *
     * @var int
     */
    private $invocationCount;

    /**
     * @var \DateTime
     */
    private $registeredAt;

    /**
     * @var int
     */
    private $busyTime;

    /**
     * @var int
     */
    private $maxSimultaneousCalls;

    /**
     * @var int
     */
    private $invocationAverageTime;

    /**
     * @var null|\DateTime
     */
    private $lastCallStartedAt;

    /**
     * @var null|\DateTime
     */
    private $lastIdledAt;

    /**
     * @var string|null
     */
    private $busyStart;

    /**
     * @var float
     */
    private $completedCallTimeTotal;

    /**
     * Constructor
     *
     * @param \Thruway\Session $session
     * @param string $procedureName
     */
    public function __construct(Session $session, $procedureName)
    {
        $this->id            = Session::getUniqueId();
        $this->session       = $session;
        $this->procedureName = $procedureName;
        $this->allowMultipleRegistrations = false;
        $this->discloseCaller = false;
        $this->calls         = [];
        $this->registeredAt  = new \DateTime();
        $this->invocationCount = 0;
        $this->busyTime      = 0;
        $this->invocationAverageTime = 0;
        $this->maxSimultaneousCalls  = 0;
        $this->lastCallStartedAt     = null;
        $this->lastIdledAt           = $this->registeredAt;
        $this->busyStart             = null;
        $this->completedCallTimeTotal = 0;
    }

    /**
     * @param Session $session
     * @param RegisterMessage $msg
     * @return Registration
     */
    static function createRegistrationFromRegisterMessage(Session $session, RegisterMessage $msg) {
        $registration = new Registration($session, $msg->getProcedureName());

        $options = (array)$msg->getOptions();
        if (isset($options['discloseCaller']) && $options['discloseCaller'] === true) {
            $registration->setDiscloseCaller(true);
        }

        if (isset($options['thruway_mutliregister']) && $options['thruway_mutliregister'] === true) {
            $registration->setAllowMultipleRegistrations(true);
        }

        return $registration;
    }

    /**
     * @return boolean
     */
    public function getAllowMultipleRegistrations()
    {
        return $this->allowMultipleRegistrations;
    }

    /**
     * @return boolean
     */
    public function isAllowMultipleRegistrations()
    {
        return $this->getAllowMultipleRegistrations();
    }

    /**
     * @param boolean $allowMultipleRegistrations
     */
    public function setAllowMultipleRegistrations($allowMultipleRegistrations)
    {
        $this->allowMultipleRegistrations = $allowMultipleRegistrations;
    }

    /**
     * @param Session $session
     * @param CallMessage $msg
     */
    public function processCall(Session $session, CallMessage $msg) {
        $invocationMessage = InvocationMessage::createMessageFrom($msg, $this);

        $details = [];
        if ($this->getDiscloseCaller() === true && $session->getAuthenticationDetails()) {
            $details = [
                "caller"     => $session->getSessionId(),
                "authid"     => $session->getAuthenticationDetails()->getAuthId(),
                //"authrole" => $session->getAuthenticationDetails()->getAuthRole(),
                "authmethod" => $session->getAuthenticationDetails()->getAuthMethod(),
            ];
        }

        // TODO: check to see if callee supports progressive call
        $callOptions   = $msg->getOptions();
        $isProgressive = false;
        if (is_array($callOptions) && isset($callOptions['receive_progress']) && $callOptions['receive_progress']) {
            $details       = array_merge($details, ["receive_progress" => true]);
            $isProgressive = true;
        }

        // if nothing was added to details - change ot stdClass so it will serialize correctly
        if (count($details) == 0) {
            $details = new \stdClass();
        }
        $invocationMessage->setDetails($details);

        $call = new Call($msg, $session, $invocationMessage, $this->getSession(), $this);

        $call->setIsProgressive($isProgressive);

        $this->calls[] = $call;

        $callCount = count($this->calls);
        if ($callCount == 1) {
            // we just became busy
            $this->busyStart = microtime();
        }
        if ($callCount > $this->maxSimultaneousCalls) $this->maxSimultaneousCalls = $callCount;
        $this->invocationCount++;
        $this->lastCallStartedAt = new \DateTime();

        $this->getSession()->sendMessage($invocationMessage);
    }

    public function getCallByRequestId($requestId) {
        /** @var Call $call */
        foreach ($this->calls as $call) {
            if ($call->getInvocationMessage()->getRequestId()) {
                return $call;
            }
        }

        return false;
    }

    public function removeCall($call) {
        /** @var Call $call */
        foreach ($this->calls as $i => $call) {
            if ($call === $this->calls[$i]) {
                array_splice($this->calls, $i, 1);
                $callEnd = microtime();

                // average call time
                $callsInAverage = $this->invocationCount - count($this->calls) - 1;

                // add this call time into the total
                $this->completedCallTimeTotal += $callEnd - $call->getCallStart();
                $callsInAverage++;
                $this->invocationAverageTime = ((float)$this->completedCallTimeTotal) / $callsInAverage;

                if (count($this->calls) == 0) {
                    $this->lastIdledAt = new \DateTime();
                    if ($this->busyStart !== null) {
                        $this->busyTime = $this->busyTime + ($callEnd - $this->busyStart);
                        $this->busyStart = null;
                    }
                }
            }
        }
    }

    /**
     * Get registration ID
     * 
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     * Get seesion
     * 
     * @return \Thruway\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get disclose caller
     * 
     * @return mixed
     */
    public function getDiscloseCaller()
    {
        return $this->discloseCaller;
    }

    /**
     * Set disclose caller
     * 
     * @param mixed $discloseCaller
     */
    public function setDiscloseCaller($discloseCaller)
    {
        $this->discloseCaller = $discloseCaller;
    }

    public function getCurrentCallCount() {
        return count($this->calls);
    }
}
