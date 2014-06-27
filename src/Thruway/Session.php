<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/8/14
 * Time: 11:15 PM
 */

namespace Thruway;


use Thruway\Message\Message;
use Thruway\Transport\InternalClientTransport;
use Thruway\Transport\TransportInterface;
use Ratchet\ConnectionInterface;

/**
 * Class Session
 * @package Thruway
 */
class Session extends AbstractSession
{

    /**
     * @var
     */
    private $authenticationProvider;


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
     * @return mixed
     */
    public function getAuthenticationProvider()
    {
        return $this->authenticationProvider;
    }

    /**
     * @param mixed $authenticationProvider
     */
    public function setAuthenticationProvider($authenticationProvider)
    {
        $this->authenticationProvider = $authenticationProvider;
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



} 