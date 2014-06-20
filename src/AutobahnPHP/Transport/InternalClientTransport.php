<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/19/14
 * Time: 11:43 AM
 */

namespace AutobahnPHP\Transport;


use AutobahnPHP\Message\Message;
use AutobahnPHP\Peer\AbstractPeer;

class InternalClientTransport implements TransportInterface {


    /**
     * @var AbstractPeer
     */
    private $farPeer;

    /**
     * @var TransportInterface
     */
    private $farPeerTransport;

    function __construct(AbstractPeer $farPeer)
    {
        $this->farPeer = $farPeer;
    }

    /**
     * @param \AutobahnPHP\Transport\TransportInterface $farPeerTransport
     */
    public function setFarPeerTransport($farPeerTransport)
    {
        $this->farPeerTransport = $farPeerTransport;
    }

    /**
     * @return \AutobahnPHP\Transport\TransportInterface
     */
    public function getFarPeerTransport()
    {
        return $this->farPeerTransport;
    }

    public function sendMessage(Message $msg)
    {
        if ($this->getFarPeerTransport() === null) throw new \Exception("You must set the farPeerTransport on internal client transports");

        $this->farPeer->onMessage($this->getFarPeerTransport(), $msg);
    }

    public function close()
    {
        // TODO: Implement close() method.
    }

} 