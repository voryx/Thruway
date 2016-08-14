<?php

namespace Thruway\Transport;

use React\EventLoop\LoopInterface;
use React\SocketClient\Connector;
use React\Stream\Stream;
use Thruway\Peer\ClientInterface;
use Thruway\Serializer\JsonSerializer;

/**
 * Class RawSocketClientTransportProvider
 *
 * Implements transport provider on raw socket for client
 *
 * @package Thruway\Transport
 */
class RawSocketClientTransportProvider extends AbstractClientTransportProvider
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
     * Constructor
     *
     * @param string $address
     * @param int $port
     */
    function __construct($address = "127.0.0.1", $port = 8181)
    {
        $this->address   = $address;
        $this->port      = $port;
        $this->transport = null;
    }

    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\ClientInterface $client
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(ClientInterface $client, LoopInterface $loop)
    {
        $this->client = $client;
        $this->loop = $loop;

        $dnsResolverFactory = new \React\Dns\Resolver\Factory();
        $dns                = $dnsResolverFactory->createCached('8.8.8.8', $loop);

        $connector = new Connector($loop, $dns);

        $connector->create($this->address, $this->port)->then(function (Stream $stream) {
            $stream->on('data', [$this, "handleData"]);
            $stream->on('close', [$this, "handleClose"]);
            $this->handleConnection($stream);
        });
    }

    /**
     * Handle process on open new connection
     *
     * @param \React\Stream\Stream $conn
     */
    public function handleConnection(Stream $conn)
    {
        //$this->getManager()->debug("Raw socket opened");

        $this->transport = new RawSocketTransport($conn, $this->loop, $this->client);

        $this->transport->setSerializer(new JsonSerializer());

        $this->transport->on('message', function ($transport, $msg) {
            $this->client->onMessage($transport, $msg);
        });

        $this->client->onOpen($this->transport);
    }

    /**
     * Handle process reveiced data
     *
     * @param mixed $data
     * @param \React\Stream\Stream $conn
     */
    public function handleData($data, Stream $conn)
    {
        $this->transport->handleData($data);
    }

    /**
     * Handle process on close connection
     *
     * @param \React\Stream\Stream $conn
     */
    public function handleClose(Stream $conn)
    {
        $this->client->onClose($this->transport);
    }
}
