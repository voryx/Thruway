<?php

namespace Voryx\ThruwayBundle\Event;


use Symfony\Component\EventDispatcher\Event;
use Thruway\ClientSession;
use Thruway\Transport\TransportInterface;

/**
 * Class SessionEvent
 * @package Voryx\ThruwayBundle\Event
 */
class SessionEvent extends Event
{
    /**
     * @var ClientSession
     */
    private $session;

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var string
     */
    private $processName;

    /**
     * @var int
     */
    private $processInstance;

    /**
     * @param ClientSession $session
     * @param TransportInterface $transport
     * @param $processName
     * @param $processInstance
     */
    function __construct(ClientSession $session, TransportInterface $transport, $processName, $processInstance)
    {
        $this->session         = $session;
        $this->transport       = $transport;
        $this->processName     = $processName;
        $this->processInstance = $processInstance;
    }

    /**
     * @return ClientSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return TransportInterface
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * @return string
     */
    public function getProcessName()
    {
        return $this->processName;
    }

    /**
     * @return int
     */
    public function getProcessInstance()
    {
        return $this->processInstance;
    }

}