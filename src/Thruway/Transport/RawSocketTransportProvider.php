<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\Server;
use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use Thruway\Serializer\JsonSerializer;

/**
 * Class RawSocketTransportProvider
 *
 * Implements a transport provider on raw socket (for router)
 *
 * @package Thruway\Transport
 */
class RawSocketTransportProvider implements TransportProviderInterface
{

    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $port;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    private $manager;

    /**
     * @var \SplObjectStorage
     */
    private $transports;

    /**
     * @var \Thruway\Peer\AbstractPeer
     */
    private $peer;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var boolean
     */
    private $trusted;

    /**
     * Constructor
     *
     * @param string $address
     * @param int $port
     */
    function __construct($address = "127.0.0.1", $port = 8181)
    {
        $this->address = $address;
        $this->port    = $port;

        $this->setManager(new ManagerDummy());

        $this->transports = new \SplObjectStorage();
    }

    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        $this->peer = $peer;
        $this->loop = $loop;

        $socket = new Server($loop);
        $socket->on('connection', [$this, "handleConnection"]);
        $socket->listen($this->port, $this->address);
    }

    /**
     * Handle process on open new connection
     *
     * @param \React\Socket\Connection $conn
     */
    public function handleConnection(Connection $conn)
    {
        Logger::debug($this, "Raw socket opened " . $conn->getRemoteAddress());

        $transport = new RawSocketTransport($conn, $this->loop, $this->peer);

        $this->transports->attach($conn, $transport);

        $transport->setSerializer(new JsonSerializer());

        $transport->setTrusted($this->trusted);

        $this->peer->onOpen($transport);

        $conn->on('data', [$this, "handleData"]);
        $conn->on('close', [$this, "handleClose"]);
    }

    /**
     * Handle process reveiced data
     *
     * @param mixed $data
     * @param \React\Socket\Connection $conn
     */
    public function handleData($data, Connection $conn)
    {
        $transport = $this->transports[$conn];

        $transport->handleData($data);
    }

    /**
     * Handle process on close transport
     *
     * @param \React\Socket\Connection $conn
     */
    public function handleClose(Connection $conn)
    {
        Logger::debug($this, "Raw socket closed " . $conn->getRemoteAddress());
        $transport = $this->transports[$conn];
        $this->transports->detach($conn);

        $this->peer->onClose($transport);
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

    /**
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $managerInterface
     */
    public function setManager(ManagerInterface $managerInterface)
    {
        $this->manager = $managerInterface;
    }

    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
    }
}
