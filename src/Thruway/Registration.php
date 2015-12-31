<?php

namespace Thruway;

use Thruway\Common\Utils;
use Thruway\Common\LeakyBucket;
use Thruway\Message\ErrorMessage;
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
     * @var int
     */
    private $limit;

    /**
     * @var LeakyBucket
     */
    private $leakyBucket;

    /**
     * @var \SplQueue
     */
    private $invokeQueue;

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
     * @var string
     */
    private $invokeType;

    /**
     * @var Call[]
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
     * @bool
     */
    private $rateLimited;

    const SINGLE_REGISTRATION = 'single';
    const ROUNDROBIN_REGISTRATION = 'roundrobin';
    const RANDOM_REGISTRATION = 'random';
    const FIRST_REGISTRATION = 'first';
    const LAST_REGISTRATION = 'last';
    const THRUWAY_REGISTRATION = '_thruway';

    /**
     * Constructor
     *
     * @param \Thruway\Session $session
     * @param string $procedureName
     */
    public function __construct(Session $session, $procedureName)
    {
        $this->id = Utils::getUniqueId();
        $this->session = $session;
        $this->procedureName = $procedureName;
        $this->allowMultipleRegistrations = false;
        $this->invokeType = 'single';
        $this->discloseCaller = false;
        $this->calls = [];
        $this->registeredAt = new \DateTime();
        $this->invocationCount = 0;
        $this->busyTime = 0;
        $this->invocationAverageTime = 0;
        $this->maxSimultaneousCalls = 0;
        $this->lastCallStartedAt = null;
        $this->lastIdledAt = $this->registeredAt;
        $this->busyStart = null;
        $this->completedCallTimeTotal = 0;

        //no throtling by default
        $this->limit = -1;
        $this->leakyBucket = new LeakyBucket();
        $this->rateLimited = false;
    }

    /**
     * Create Registration from RegisterMessage
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\RegisterMessage $msg
     * @return \Thruway\Registration
     */
    public static function createRegistrationFromRegisterMessage(Session $session, RegisterMessage $msg)
    {
        $registration = new Registration($session, $msg->getProcedureName());
        $options = $msg->getOptions();

        if (isset($options->disclose_caller) && $options->disclose_caller === true) {
            $registration->setDiscloseCaller(true);
        }

        if (isset($options->invoke)) {
            $registration->setInvokeType($options->invoke);
        } else {
            if (isset($options->thruway_multiregister) && $options->thruway_multiregister === true) {
                $registration->setInvokeType(Registration::THRUWAY_REGISTRATION);
            } else {
                $registration->setInvokeType(Registration::SINGLE_REGISTRATION);
            }
        }
        if (isset($options->_limit) && settype($options->_limit, "integer")) {
            $registration->setLimit($options->_limit);
        } else {
            $registration->setLimit(-1); //setting to UNLIMITED
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
     * 
     * @return String
     */
    public function getInvokeType()
    {
        return $this->invokeType;
    }

    /**
     * 
     * @param String $type
     */
    public function setInvokeType($type)
    {
        $type = strtolower($type);
        $allowedRegistrations = array(
            Registration::SINGLE_REGISTRATION,
            Registration::ROUNDROBIN_REGISTRATION,
            Registration::RANDOM_REGISTRATION,
            Registration::THRUWAY_REGISTRATION,
            Registration::FIRST_REGISTRATION,
            Registration::LAST_REGISTRATION
        );
        if (in_array($type, $allowedRegistrations)) {
            if ($type !== Registration::SINGLE_REGISTRATION) {
                $this->invokeType = $type;
                $this->setAllowMultipleRegistrations(true);
            } else {
                $this->invokeType = Registration::SINGLE_REGISTRATION;
                $this->setAllowMultipleRegistrations(false);
            }
        }
    }

    /**
     * @param int $limit The number of calls allowed per second
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        if ($limit > 0) {
            $this->rateLimited = true;
            $this->invokeQueue = new \SplQueue();
            $this->leakyBucket = new Common\LeakyBucket($this->limit);
        }
    }

    /**
     * Get the Limit per second on this registrations
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
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
        if ($callCount > $this->maxSimultaneousCalls) {
            $this->maxSimultaneousCalls = $callCount;
        }
        $this->invocationCount++;
        $this->lastCallStartedAt = new \DateTime();
        if ($this->rateLimited) {
            if ($this->leakyBucket->canConsume()) {
                $this->leakyBucket->consume();
                $this->getSession()->sendMessage($call->getInvocationMessage());
            } else {
                $this->invokeQueue->enqueue($call->getInvocationMessage());
                if ($this->invokeQueue->count() === 1) {
                    //start the timer if I am the first addition to the queue
                    $this->session->getLoop()->addTimer($this->leakyBucket->getTimeLeft() / 1000, $this);
                }
            }
        } else {
            $this->getSession()->sendMessage($call->getInvocationMessage());
        }
    }
    
    /**
     * Get whether rate limited
     *
     * @return bool
     */
    public function isRateLimited(){
        return $this->rateLimited;
    }

    /**
     * Process Invocation Queue
     * 
     * Using __invoke magic method
     * 
     * @param none
     */
    public function __invoke()
    {
        $this->getSession()->sendMessage($this->invokeQueue->dequeue());
        if ($this->invokeQueue->count() > 0) {
            $this->session->getLoop()->addTimer($this->leakyBucket->getTimeLeft() / 1000, $this);
        }
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
                $this->invocationAverageTime = ((float) $this->completedCallTimeTotal) / $callsInAverage;

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
     * This will send error messages on all pending calls
     * This is used when a session disconnects before completing a call
     */
    public function errorAllPendingCalls()
    {
        foreach ($this->calls as $call) {
            $call->getCallerSession()->sendMessage(ErrorMessage::createErrorMessageFromMessage($call->getCallMessage(), 'wamp.error.canceled'));
        }
    }

    /**
     * Get registration statistics
     *
     * @return array
     */
    public function getStatistics()
    {
        return [
            'currentCallCount' => count($this->calls),
            'registeredAt' => $this->registeredAt,
            'invocationCount' => $this->invocationCount,
            'invocationAverageTime' => $this->invocationAverageTime,
            'busyTime' => $this->busyTime,
            'busyStart' => $this->busyStart,
            'lastIdledAt' => $this->lastIdledAt,
            'lastCallStartedAt' => $this->lastCallStartedAt,
            'completedCallTimeTotal' => $this->completedCallTimeTotal,
            'invokeQueueCount' => $this->rateLimited ? $this->invokeQueue->count() : 0
        ];
    }

}
