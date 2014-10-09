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
     * Constructor
     *
     * @param \Thruway\Session $session
     * @param string $procedureName
     */
    function __construct(Session $session, $procedureName)
    {
        $this->id            = Session::getUniqueId();
        $this->session       = $session;
        $this->procedureName = $procedureName;
        $this->allowMultipleRegistrations = false;
        $this->discloseCaller = false;
        $this->calls         = [];
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
            }
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @return \Thruway\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return mixed
     */
    public function getDiscloseCaller()
    {
        return $this->discloseCaller;
    }

    /**
     * @param mixed $discloseCaller
     */
    public function setDiscloseCaller($discloseCaller)
    {
        $this->discloseCaller = $discloseCaller;
    }

}