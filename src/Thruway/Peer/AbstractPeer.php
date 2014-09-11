<?php

namespace Thruway\Peer;

use Thruway\Manager\ManagerInterface;
use Thruway\Message\Message;
use Thruway\Transport\AbstractTransportProvider;
use Thruway\Transport\TransportInterface;

abstract class AbstractPeer
{
    /**
     * @var ManagerInterface
     */
    protected $manager;

    abstract public function onMessage(TransportInterface $transport, Message $msg);

    abstract public function onOpen(TransportInterface $transport);

    abstract public function addTransportProvider(AbstractTransportProvider $transportProvider);

    abstract public function setManager($manager);

    // start can take an argument of $runLoop = true
    // not added here because it would break other people's stuff who
    // don't implement that
    abstract public function start();

}
