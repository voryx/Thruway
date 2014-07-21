<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/8/14
 * Time: 11:15 PM
 */

namespace Thruway;


use Thruway\Message\Message;
use Thruway\Transport\TransportInterface;

/**
 * Class Session
 * @package Thruway
 */
class Session extends AbstractSession
{

    /**
     * @var AuthenticationDetails
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
     * @var ManagerInterface
     */
    private $manager;

    function __construct(TransportInterface $transport, ManagerInterface $manager = null)
    {
        $this->transport = $transport;
        $this->state = static::STATE_PRE_HELLO;
        $this->sessionId = static::getUniqueId();
        $this->realm = null;

        $this->messagesSent = 0;
        $this->sessionStart = new \DateTime();

        if ($manager === null) $manager = new ManagerDummy();

        $this->manager = $manager;

        $this->authenticationDetails = null;
    }

    public function sendMessage(Message $msg)
    {
        $this->messagesSent++;
        $this->transport->sendMessage($msg);
    }

    public function shutdown()
    {

        $this->transport->close();
    }

    /**
     *
     */
    public function onClose()
    {
        if ($this->realm !== null) {
            $this->realm->leave($this);
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
     * @param \Thruway\ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * @return \Thruway\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    public function getMessagesSent() {
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
     * @param \Thruway\AuthenticationDetails $authenticationDetails
     */
    public function setAuthenticationDetails($authenticationDetails)
    {
        $this->authenticationDetails = $authenticationDetails;
    }

    /**
     * @return \Thruway\AuthenticationDetails
     */
    public function getAuthenticationDetails()
    {
        return $this->authenticationDetails;
    }



} 