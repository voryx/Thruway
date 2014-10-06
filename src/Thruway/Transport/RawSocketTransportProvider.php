<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use React\Socket\Connection;
use React\Socket\Server;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use Thruway\Serializer\JsonSerializer;

class RawSocketTransportProvider implements TransportProviderInterface {
    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $port;

    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * @var \SplObjectStorage
     */
    private $transports;

    /**
     * @var AbstractPeer
     */
    private $peer;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @param string $address
     * @param int $port
     */
    function __construct($address = "127.0.0.1", $port = 8181)
    {
        $this->address = $address;
        $this->port = $port;

        $this->setManager(new ManagerDummy());

        $this->transports = new \SplObjectStorage();
    }

    /**
     * @param AbstractPeer $peer
     * @param LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        $this->peer = $peer;
        $this->loop = $loop;

        $socket = new Server($loop);
        $socket->on('connection', [$this, "handleConnection"]);
        $socket->listen($this->port, $this->address);
    }

    public function handleConnection(Connection $conn) {
        $this->getManager()->debug("Raw socket opened " . $conn->getRemoteAddress());

        $transport = new RawSocketTransport($conn, $this->loop, $this->peer);

        $this->transports->attach($conn, $transport);

        $transport->setSerializer(new JsonSerializer());

        $this->peer->onOpen($transport);

        $conn->on('data', [$this, "handleData"]);
        $conn->on('close', [$this, "handleClose"]);
    }

    public function handleData($data, Connection $conn) {
        $transport = $this->transports[$conn];

        $transport->handleData($data);
    }

    public function handleClose(Connection $conn) {
        $this->getManager()->debug("Raw socket closed " . $conn->getRemoteAddress());
        $transport = $this->transports[$conn];
        $this->transports->detach($conn);

        $this->peer->onClose($transport);
    }

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param ManagerInterface $managerInterface
     */
    public function setManager(ManagerInterface $managerInterface)
    {
        $this->manager = $managerInterface;
    }

} 