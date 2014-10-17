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
     * Create Registration from RegisterMessage
     * 
     * @param \Thruway\Session $session
     * @param \Thruway\Message\RegisterMessage $msg
     * @return \Thruway\Registration
     */
    public static function createRegistrationFromRegisterMessage(Session $session, RegisterMessage $msg) {
        $registration = new Registration($session, $msg->getProcedureName());

        $options = (array)$msg->getOptions();
        if (isset($options['disclose_caller']) && $options['disclose_caller'] === true) {
            $registration->setDiscloseCaller(true);
        }

        if (isset($options['thruway_multiregister']) && $options['thruway_multiregister'] === true) {
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
     * Process call
     *
     * @param Call $call
     * @throws \Exception
     */
    public function processCall(Call $call)
    {
        if ($call->getRegistration() !== null) {
            throw new \Exception("Registration already set when asked to process call");
        }
        $call->setRegistration($this);

        $this->calls[] = $call;

        $this->session->incPendingCallCount();
        $callCount = count($this->calls);
        if ($callCount == 1) {
            // we just became busy
            $this->busyStart = microtime(true);
        }
        if ($callCount > $this->maxSimultaneousCalls) $this->maxSimultaneousCalls = $callCount;
        $this->invocationCount++;
        $this->lastCallStartedAt = new \DateTime();

        $this->getSession()->sendMessage($call->getInvocationMessage());
    }

    /**
     * Get call by request ID
     * 
     * @param int $requestId
     * @return boolean
     */
    public function getCallByRequestId($requestId) 
    {
        /** @var Call $call */
        foreach ($this->calls as $call) {
            if ($call->getInvocationMessage()->getRequestId() == $requestId) {
                return $call;
            }
        }

        return false;
    }

    /**
     * Remove call
     * 
     * @param \Thruway\Call $callToRemove
     */
    public function removeCall($callToRemove)
    {
        /* @var $call \Thruway\Call */
        foreach ($this->calls as $i => $call) {
            if ($callToRemove === $call) {
                array_splice($this->calls, $i, 1);
                $this->session->decPendingCallCount();
                $callEnd = microtime(true);

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

    /**
     * Get current call count
     * 
     * @return int
     */
    public function getCurrentCallCount() 
    {
        return count($this->calls);
    }

    /**
     * Get registration statistics
     *
     * @return array
     */
    public function getStatistics() {
        return [
            'currentCallCount' => count($this->calls),
            'registeredAt' => $this->registeredAt,
            'invocationCount' => $this->invocationCount,
            'invocationAverageTime' => $this->invocationAverageTime,
            'busyTime' => $this->busyTime,
            'busyStart' => $this->busyStart,
            'lastIdledAt' => $this->lastIdledAt,
            'lastCallStartedAt' => $this->lastCallStartedAt,
            'completedCallTimeTotal' => $this->completedCallTimeTotal

        ];
    }
}
