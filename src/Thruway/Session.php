<?php

namespace Thruway;


use Thruway\Authentication\AuthenticationDetails;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;

/**
 * Class Session
 * 
 * @package Thruway
 */
class Session extends AbstractSession
{

    /**
     * @var \Thruway\Authentication\AuthenticationDetails
     */
    private $authenticationDetails;


    /**
     * @var int
     */
    private $messagesSent;

    /**
     * @var \DateTime
     */
    private $sessionStart;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * Constructor
     * 
     * @param \Thruway\Transport\TransportInterface $transport
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    function __construct(TransportInterface $transport, ManagerInterface $manager = null)
    {
        $this->transport = $transport;
        $this->state = static::STATE_PRE_HELLO;
        $this->sessionId = static::getUniqueId();
        $this->realm = null;

        $this->messagesSent = 0;
        $this->sessionStart = new \DateTime();

        if ($manager === null) {
            $manager = new ManagerDummy();
        }

        $this->setManager($manager);

        $this->authenticationDetails = null;
    }

    /**
     * Send message
     * 
     * @param \Thruway\Message\Message $msg
     */
    public function sendMessage(Message $msg)
    {
        $this->messagesSent++;
        $this->transport->sendMessage($msg);
    }



    /**
     * Handle close session
     */
    public function onClose()
    {
        if ($this->realm !== null) {
            $this->realm->leave($this);
            $this->realm = null;
        }
    }

    /**
     * Generate a unique id for sessions and requests
     * @return mixed
     */
    static public function getUniqueId()
    {
        // TODO: make this better
        $result = sscanf(uniqid(), "%x");

        return $result[0];
    }

    /**
     * @param \Thruway\Manager\ManagerInterface $manager
     * @throws \InvalidArgumentException
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return int
     */
    public function getMessagesSent()
    {
        return $this->messagesSent;
    }

    /**
     * @return \DateTime
     */
    public function getSessionStart()
    {
        return $this->sessionStart;
    }

    /**
     * @param \Thruway\Authentication\AuthenticationDetails $authenticationDetails
     */
    public function setAuthenticationDetails($authenticationDetails)
    {
        $this->authenticationDetails = $authenticationDetails;
    }

    /**
     * @return \Thruway\Authentication\AuthenticationDetails
     */
    public function getAuthenticationDetails()
    {
        return $this->authenticationDetails;
    }


}