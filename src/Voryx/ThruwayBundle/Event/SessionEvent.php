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
     * @param $session
     * @param $transport
     */
    function __construct(ClientSession $session, TransportInterface $transport)
    {
        $this->session   = $session;
        $this->transport = $transport;
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


}