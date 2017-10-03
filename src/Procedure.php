<?php

namespace Thruway;

use Thruway\Message\ErrorMessage;
use Thruway\Message\RegisteredMessage;
use Thruway\Message\RegisterMessage;
use Thruway\Message\UnregisteredMessage;
use Thruway\Message\UnregisterMessage;
use SplQueue;

/**
 * Class Procedure
 *
 * The Procedure class is used by the Dealer to keep track of registered procedures.
 *
 * @package Thruway
 */
class Procedure
{
    /**
     * @var string
     */
    private $procedureName;

    /**
     * @var Registration[]
     */
    private $registrations;

    /**
     * @var bool
     */
    private $allowMultipleRegistrations;

    /**
     * @var string
     */
    private $invokeType;

    /**
     * @var bool
     */
    private $discloseCaller;

    /**
     * @var \SplQueue
     */
    private $callQueue;

    /**
     * Constructor
     *
     * @param string $procedureName
     */
    public function __construct($procedureName)
    {
        $this->setProcedureName($procedureName);

        $this->registrations              = [];
        $this->allowMultipleRegistrations = false;
        $this->invokeType                 = Registration::SINGLE_REGISTRATION;
        $this->discloseCaller             = false;

        $this->callQueue = new SplQueue();
    }

    /**
     * Process register
     *
     * @param Session $session
     * @param \Thruway\Message\RegisterMessage $msg
     * @return bool
     * @throws \Exception
     */
    public function processRegister(Session $session, RegisterMessage $msg)
    {
        $registration = Registration::createRegistrationFromRegisterMessage($session, $msg);

        if (count($this->registrations) > 0) {
            // we already have something registered
            if ($this->getAllowMultipleRegistrations()) {
                return $this->addRegistration($registration, $msg);
            } else {
                // we are not allowed multiple registrations, but we may want
                // to replace an orphaned session
                $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.procedure_already_exists');

                $options = $msg->getOptions();
                // get the existing registration
                /** @var Registration $oldRegistration */
                $oldRegistration = $this->registrations[0];
                if (isset($options->replace_orphaned_session) && ($options->replace_orphaned_session === 'yes' || $options->replace_orphaned_session === true)) {
                    try {
                        $oldRegistration->getSession()->ping(5)
                            ->then(function ($res) use ($session, $errorMsg) {
                                // the ping came back - send procedure_already_exists
                                $session->sendMessage($errorMsg);
                            }, function ($r) use ($oldRegistration, $session, $registration, $msg) {
                                // bring down the exiting session because the
                                // ping timed out
                                $deadSession = $oldRegistration->getSession();

                                // this should do all the cleanup needed and remove the
                                // registration from this procedure also
                                $deadSession->shutdown();

                                // complete this registration now
                                return $this->addRegistration($registration, $msg);
                            });
                    } catch (\Exception $e) {
                        $session->sendMessage($errorMsg);
                    }
                } else {
                    $session->sendMessage($errorMsg);
                }
            }
        } else {
            // this is the first registration
            // setup the procedure to match the options
            $this->setDiscloseCaller($registration->getDiscloseCaller());
            $this->setAllowMultipleRegistrations($registration->getAllowMultipleRegistrations());
            $this->setInvokeType($registration->getInvokeType());

            return $this->addRegistration($registration, $msg);
        }
    }

    /**
     * Add registration
     *
     * @param \Thruway\Registration $registration
     * @return bool
     * @throws \Exception
     */
    private function addRegistration(Registration $registration, RegisterMessage $msg)
    {
        try {
            // make sure the uri is exactly the same
            if ($registration->getProcedureName() !== $this->getProcedureName()) {
                throw new \Exception('Attempt to add registration to procedure with different procedure name.');
            }

            // make sure options match
            if (strcasecmp($registration->getInvokeType(), $this->getInvokeType()) !== 0) {
                throw new \Exception('Registration and procedure must agree on invocation type');
            }
            if ($registration->getDiscloseCaller() !== $this->getDiscloseCaller()) {
                throw new \Exception('Registration and procedure must agree on disclose caller');
            }

            $this->registrations[] = $registration;

            $registration->getSession()->sendMessage(new RegisteredMessage($msg->getRequestId(), $registration->getId()));

            // now that we have added a new registration, process the queue if we are using it
            if ($this->getAllowMultipleRegistrations()) {
                $this->processQueue();
            }

            return true;
        } catch (\Exception $e) {
            $registration->getSession()->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));

            return false;
        }
    }

    /**
     * Get registration by ID
     *
     * @param $registrationId
     * @return bool|Registration
     */
    public function getRegistrationById($registrationId)
    {
        /** @var Registration $registration */
        foreach ($this->registrations as $registration) {
            if ($registration->getId() === $registrationId) {
                return $registration;
            }
        }

        return false;
    }

    /**
     * process unregister
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\UnregisterMessage $msg
     * @return bool
     */
    public function processUnregister(Session $session, UnregisterMessage $msg)
    {
        for ($i = 0; $i < count($this->registrations); $i++) {
            /** @var Registration $registration */
            $registration = $this->registrations[$i];
            if ($registration->getId() == $msg->getRegistrationId()) {

                // make sure the session is the correct session
                if ($registration->getSession() !== $session) {
                    $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.no_such_registration'));
                    //$this->manager->warning('Tried to unregister a procedure that belongs to a different session.');
                    return false;
                }

                array_splice($this->registrations, $i, 1);

                // TODO: need to handle any calls that are hanging around

                $session->sendMessage(UnregisteredMessage::createFromUnregisterMessage($msg));
                return true;
            }
        }

        $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.no_such_registration'));

        return false;
    }

    /**
     * Process call
     *
     * @param \Thruway\Session $session
     * @param Call $call
     * @throws \Exception
     * @return bool | Call
     */
    public function processCall(Session $session, Call $call)
    {
        // find a registration to call
        if (count($this->registrations) === 0) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($call->getCallMessage(), 'wamp.error.no_such_procedure'));
            return false;
        }

        // just send it to the first one if we don't allow multiple registrations
        if (!$this->getAllowMultipleRegistrations()) {
            $this->registrations[0]->processCall($call);
        } else {
            $this->callQueue->enqueue($call);
            $this->processQueue();
        }

        return true;
    }

    /**
     * Process the Queue
     *
     * @throws \Exception
     */
    public function processQueue()
    {
        if (!$this->getAllowMultipleRegistrations()) {
            throw new \Exception('Queuing only allowed when there are multiple registrations');
        }

        // find the best candidate
        while ($this->callQueue->count() > 0) {
            $registration = NULL;

            if (strcasecmp($this->getInvokeType(), Registration::FIRST_REGISTRATION) === 0) {
                $registration = $this->getNextFirstRegistration();
            } else if (strcasecmp($this->getInvokeType(), Registration::LAST_REGISTRATION) === 0) {
                $registration = $this->getNextLastRegistration();
            } else if (strcasecmp($this->getInvokeType(), Registration::RANDOM_REGISTRATION) === 0) {
                $registration = $this->getNextRandomRegistration();
            } else if (strcasecmp($this->getInvokeType(), Registration::ROUNDROBIN_REGISTRATION) === 0) {
                $registration = $this->getNextRoundRobinRegistration();
            } else if (strcasecmp($this->getInvokeType(), Registration::THRUWAY_REGISTRATION) === 0) {
                $registration = $this->getNextThruwayRegistration();
            }

            if ($registration === NULL) {
                break;
            }
            $call = $this->callQueue->dequeue();
            $registration->processCall($call);
        }
    }

    private function getNextThruwayRegistration()
    {
        $congestion = true;
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $this->registrations[0];
        /* @var $registration \Thruway\Registration */
        foreach ($this->registrations as $registration) {
            if ($registration->getSession()->getPendingCallCount() == 0) {
                $bestRegistration = $registration;
                $congestion       = false;
                break;
            }
            if ($registration->getSession()->getPendingCallCount() <
                $bestRegistration->getSession()->getPendingCallCount()
            ) {
                $bestRegistration = $registration;
            }
        }
        if ($congestion) {
            // there is congestion
            $bestRegistration->getSession()->getRealm()->publishMeta('thruway.metaevent.procedure.congestion', [
                    ['name' => $this->getProcedureName()]]
            );
            return NULL;
        }
        return $bestRegistration;
    }

    private function getNextRandomRegistration()
    {
        if (count($this->registrations) === 1) {
            //just return this so that we don't have to run mt_rand
            return $this->registrations[0];
        }
        //mt_rand is apparently faster than array_rand(which uses the libc generator)
        return $this->registrations[mt_rand(0, count($this->registrations) - 1)];
    }

    private function getNextRoundRobinRegistration()
    {
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $this->registrations[0];
        /* @var $registration \Thruway\Registration */
        foreach ($this->registrations as $registration) {
            if ($registration->getStatistics()['lastCallStartedAt'] <
                $bestRegistration->getStatistics()['lastCallStartedAt']) {
                $bestRegistration = $registration;
                break;
            }
        }
        return $bestRegistration;
    }

    private function getNextFirstRegistration()
    {
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $this->registrations[0];
        return $bestRegistration;
    }

    private function getNextLastRegistration()
    {
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $this->registrations[count($this->registrations) - 1];
        return $bestRegistration;
    }

    /**
     * Remove all references to Call to it can be GCed
     *
     * @param Call $call
     */
    public function removeCall(Call $call)
    {
        $newQueue = new \SplQueue();
        while (!$this->callQueue->isEmpty()) {
            $c = $this->callQueue->dequeue();

            if ($c === $call)
                continue;

            $newQueue->enqueue($c);
        }
        $this->callQueue = $newQueue;

        $registration = $call->getRegistration();
        if ($registration) {
            $registration->removeCall($call);
        }
    }

    /**
     * Get call by request ID
     *
     * @param int $requestId
     * @return \Thruway\Call|boolean
     */
    public function getCallByRequestId($requestId)
    {
        /* @var $registration \Thruway\Registration */
        foreach ($this->registrations as $registration) {
            $call = $registration->getCallByRequestId($requestId);
            if ($call) {
                return $call;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @param string $procedureName
     */
    private function setProcedureName($procedureName)
    {
        $this->procedureName = $procedureName;
    }

    /**
     * @return boolean
     */
    public function isDiscloseCaller()
    {
        return $this->getDiscloseCaller();
    }

    /**
     * @return boolean
     */
    public function getDiscloseCaller()
    {
        return $this->discloseCaller;
    }

    /**
     * @param boolean $discloseCaller
     */
    public function setDiscloseCaller($discloseCaller)
    {
        $this->discloseCaller = $discloseCaller;
    }

    /**
     * @return string
     */
    public function getInvokeType()
    {
        return $this->invokeType;
    }

    /**
     * @param string $invokeType
     */
    public function setInvokeType($invoketype)
    {
        $this->invokeType = $invoketype;
    }

    /**
     * @return boolean
     */
    public function isAllowMultipleRegistrations()
    {
        return $this->getAllowMultipleRegistrations();
    }

    /**
     * @return boolean
     */
    public function getAllowMultipleRegistrations()
    {
        return $this->allowMultipleRegistrations;
    }

    /**
     * @param boolean $allowMultipleRegistrations
     */
    public function setAllowMultipleRegistrations($allowMultipleRegistrations)
    {
        $this->allowMultipleRegistrations = $allowMultipleRegistrations;
    }

    /**
     * @return Registration[]
     */
    public function getRegistrations()
    {
        return $this->registrations;
    }

    /**
     * process session leave
     *
     * @param \Thruway\Session $session
     */
    public function leave(Session $session)
    {
        // remove all registrations that belong to this session
        /* @var $registration \Thruway\Registration */
        foreach ($this->registrations as $i => $registration) {
            if ($registration->getSession() === $session) {
                // if this session is the callee on pending calls - error them out
                $registration->errorAllPendingCalls();
                array_splice($this->registrations, $i, 1);
            }
        }
    }

    /**
     * todo: This was part of the manager stuff - but may be used by some tests
     *
     */
    public function managerGetRegistrations()
    {
        $registrations = $this->getRegistrations();

        $regInfo = [];
        /** @var Registration $reg */
        foreach ($registrations as $reg) {
            $regInfo[] = [
                'id'                    => $reg->getId(),
                'invoke'                => $reg->getInvokeType(),
                'thruway_multiregister' => $reg->getAllowMultipleRegistrations(),
                'disclose_caller'       => $reg->getDiscloseCaller(),
                'session'               => $reg->getSession()->getSessionId(),
                'authid'                => $reg->getSession()->getAuthenticationDetails()->getAuthId(),
                'statistics'            => $reg->getStatistics()
            ];
        }
    }
}
