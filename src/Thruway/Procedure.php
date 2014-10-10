<?php


namespace Thruway;

use Thruway\Message\CallMessage;
use Thruway\Message\ErrorMessage;
use Thruway\Message\InvocationMessage;
use Thruway\Message\RegisteredMessage;
use Thruway\Message\RegisterMessage;
use Thruway\Message\UnregisteredMessage;
use Thruway\Message\UnregisterMessage;


/**
 * Class Procedure
 *
 * The Procedure class is used by the Dealer to keep track of registered procedures.
 *
 * @package Thruway
 */
class Procedure {
    /**
     * @var string
     */
    private $procedureName;

    /**
     * @var array
     */
    private $registrations;

    /**
     * @var bool
     */
    private $allowMultipleRegistrations;

    /**
     * @var bool
     */
    private $discloseCaller;

    /**
     * Constructor
     * 
     * @param string $procedureName
     */
    public function __construct($procedureName)
    {
        $this->setProcedureName($procedureName);

        $this->registrations = [];
        $this->allowMultipleRegistrations = false;
        $this->discloseCaller = false;
    }

    /**
     * Process register
     * 
     * @param Session $session
     * @param \Thruway\Message\RegisterMessage $msg
     * @throws \Exception
     */
    public function processRegister(Session $session, RegisterMessage $msg) 
    {
        $registration = Registration::createRegistrationFromRegisterMessage($session, $msg);

        if (count($this->registrations) > 0) {
            // we already have something registered
            if ($this->getAllowMultipleRegistrations()) {
                try {
                    $this->addRegistration($registration);
                    $session->sendMessage(
                        new RegisteredMessage($msg->getRequestId(), $registration->getId())
                    );
                } catch (\Exception $e) {
                    $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
                }
            } else {
                // we are not allowed multiple registrations, but we may want
                // to replace an orphaned session
                $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.procedure_already_exists');

                $options = $msg->getOptions();
                // get the existing registration
                /** @var Registration $oldRegistration */
                $oldRegistration = $this->registrations[0];
                if (isset($options['replace_orphaned_session']) && $options['replace_orphaned_session'] == "yes") {
                    $oldRegistration->getSession()->ping(5)
                        ->then(function ($res) use ($session, $errorMsg) {
                                // the ping came back - send procedure_already_exists
                                $session->sendMessage($errorMsg);
                            },
                            function ($r) use ($oldRegistration, $session, $registration, $msg) {
                                // bring down the exiting session because the
                                // ping timed out
                                $deadSession = $oldRegistration->getSession();

                                // this should do all the cleanup needed and remove the
                                // registration from this procedure also
                                $deadSession->shutdown();

                                // complete this registration now
                                try {
                                    $this->addRegistration($registration);
                                    $session->sendMessage(
                                        new RegisteredMessage($msg->getRequestId(), $registration->getId())
                                    );
                                } catch (\Exception $e) {
                                    $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
                                }
                            });
                } else {
                    $session->sendMessage($errorMsg);
                }
            }
        } else {
            // this is the first registration
            // setup the procedure to match the options
            $this->setDiscloseCaller($registration->getDiscloseCaller());
            $this->setAllowMultipleRegistrations($registration->getAllowMultipleRegistrations());

            try {
                $this->addRegistration($registration);
                $session->sendMessage(new RegisteredMessage($msg->getRequestId(), $registration->getId()));
            } catch (\Exception $e) {
                $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
            }
        }
    }

    /**
     * Add registration
     * 
     * @param \Thruway\Registration $registration
     * @throws \Exception
     */
    private function addRegistration(Registration $registration) 
    {
        // make sure it isn't already in here
        for ($i = 0; $i < count($this->registrations); $i++) {
            if ($registration === $this->registrations[$i]) {
                throw new \Exception('Attempt to add registration to procedure it is already added to.');
            }
        }

        // make sure the uri is exactly the same
        if ($registration->getProcedureName() != $this->getProcedureName()) {
            throw new \Exception('Attempt to add registration to procedure with different procedure name.');
        }

        // make sure options match
        if ($registration->getAllowMultipleRegistrations() != $this->getAllowMultipleRegistrations()) {
            throw new \Exception('Registration and procedure must agree on allowing multiple registrations');
        }
        if ($registration->getDiscloseCaller() != $this->getDiscloseCaller()) {
            throw new \Exception('Registration and procedure must agree on disclose caller');
        }

        $this->registrations[] = $registration;
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
            if ($registration->getId() == $registrationId) {
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
     */
    public function processUnregister(Session $session, UnregisterMessage $msg) 
    {
        for ($i = 0; $i < count($this->registrations); $i++) {
            /** @var Registration $registration */
            $registration = $this->registrations[$i];
            if ($registration->getId() == $msg->getRegistrationId()) {

                // make sure the session is the correct session
                if ($registration->getSession() !== $session) {
                    $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, "wamp.error.no_such_registration"));
                    //$this->manager->warning("Tried to unregister a procedure that belongs to a different session.");
                    return;
                }

                array_splice($this->registrations, $i, 1);

                // TODO: need to error out any calls that are hanging around
                // from this registration

                $session->sendMessage(UnregisteredMessage::createFromUnregisterMessage($msg));
                return;
            }
        }

        $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.no_such_procedure'));
    }

    /**
     * Process call
     * 
     * @param \Thruway\Session $session
     * @param \Thruway\Message\CallMessage $msg
     * @return void
     */
    public function processCall(Session $session, CallMessage $msg) 
    {
        // find a registration to call
        if (count($this->registrations) == 0) {
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg, 'wamp.error.no_such_procedure'));
            return;
        }

        // find the best candidate
        /* @var $bestRegistration \Thruway\Registration */
        $bestRegistration = $this->registrations[0];
        /* @var $registration \Thruway\Registration */
        foreach($this->registrations as $registration) {
            if ($registration->getCurrentCallCount() == 0) {
                $bestRegistration = $registration;
                break;
            }
            if ($registration->getCurrentCallCount() < $bestRegistration->getCurrentCallCount()) {
                $bestRegistration = $registration;
            }
        }

        $bestRegistration->processCall($session, $msg);
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
     * @return array
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
        foreach($this->registrations as $i => $registration) {
            if ($registration->getSession() === $session) {
                array_splice($this->registrations, $i, 1);
            }
        }
    }
}