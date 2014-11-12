<?php

namespace Thruway\Transport;


use Thruway\Manager\ManagerInterface;

abstract class AbstractTransportProvider
{

    /**
     * @var boolean
     */
    protected $trusted;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    protected $manager;

    /**
     * @var \Thruway\Peer\AbstractPeer
     */
    protected $peer;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var \Thruway\Transport\RawSocketTransport
     */
    protected $transport;


    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
    }

    /**
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Get manager
     *
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }
}