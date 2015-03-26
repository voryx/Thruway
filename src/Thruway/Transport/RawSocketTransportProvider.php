<?php

namespace Thruway\Transport;

use React\Socket\Connection;
use React\Socket\Server;
use Thruway\Event\NewConnectionEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Logging\Logger;
use Thruway\Serializer\JsonSerializer;

/**
 * Class RawSocketTransportProvider
 *
 * Implements a transport provider on raw socket (for router)
 *
 * @package Thruway\Transport
 */
class RawSocketTransportProvider extends AbstractRouterTransportProvider
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
     * @var \SplObjectStorage
     */
    private $transports;

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

        $this->transports = new \SplObjectStorage();
    }

    /**
     * Handle process on open new connection
     *
     * @param \React\Socket\Connection $conn
     */
    public function handleConnection(Connection $conn)
    {
        Logger::debug($this, "Raw socket opened " . $conn->getRemoteAddress());

        $transport = new RawSocketTransport($conn, $this->loop, $this->router);

        $this->transports->attach($conn, $transport);

        $transport->setSerializer(new JsonSerializer());

        $transport->setTrusted($this->trusted);

        $this->router->getEventDispatcher()->dispatch("new_connection", new NewConnectionEvent($transport));

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

        $this->router->onClose($transport);
    }

    public function handleRouterStart(RouterStartEvent $event) {
        $socket = new Server($this->loop);
        $socket->on('connection', [$this, "handleConnection"]);

        Logger::info($this, "Raw socket listening on " . $this->address . ":" . $this->port);

        $socket->listen($this->port, $this->address);
    }

    public static function getSubscribedEvents()
    {
        return [
            "router.start" => ['handleRouterStart', 10]
        ];
    }


}
