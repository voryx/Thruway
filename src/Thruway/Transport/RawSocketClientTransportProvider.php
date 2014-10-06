<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use React\SocketClient\Connector;
use React\Stream\Stream;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use Thruway\Serializer\JsonSerializer;

class RawSocketClientTransportProvider implements TransportProviderInterface {
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
     * @var AbstractPeer
     */
    private $peer;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var RawSocketTransport
     */
    private $transport;

    /**
     * @param string $address
     * @param int $port
     */
    function __construct($address = "127.0.0.1", $port = 8181)
    {
        $this->address = $address;
        $this->port = $port;

        $this->transport = null;
    }

    /**
     * @param AbstractPeer $peer
     * @param LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        $this->peer = $peer;
        $this->loop = $loop;

        $dnsResolverFactory = new \React\Dns\Resolver\Factory();
        $dns = $dnsResolverFactory->createCached('8.8.8.8', $loop);

        $connector = new Connector($loop, $dns);

        $connector->create($this->address, $this->port)->then(function (Stream $stream) {
                $stream->on('data', [$this, "handleData"]);
                $stream->on('close', [$this, "handleClose"]);
                $this->handleConnection($stream);
            });
    }

    public function handleConnection(Stream $conn) {
        //$this->getManager()->debug("Raw socket opened");

        $this->transport = new RawSocketTransport($conn, $this->loop, $this->peer);

        $this->transport->setSerializer(new JsonSerializer());

        $this->peer->onOpen($this->transport);
    }

    public function handleData($data, Stream $conn) {

        $this->transport->handleData($data);
    }

    public function handleClose(Stream $conn) {
        //$this->getManager()->debug("Raw socket closed " . $conn->getRemoteAddress());

        $this->peer->onClose($this->transport);
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