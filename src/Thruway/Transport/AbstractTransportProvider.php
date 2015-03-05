<?php

namespace Thruway\Transport;

use Thruway\Module\RouterModule;

abstract class AbstractTransportProvider extends RouterModule implements TransportProviderInterface
{
    /**
     * @var boolean
     */
    protected $trusted;

    /**
     * @var \Thruway\Peer\PeerInterface
     */
    protected $peer;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

    /**
     * @var \Thruway\Transport\TransportInterface
     */
    protected $transport;


    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
    }
}