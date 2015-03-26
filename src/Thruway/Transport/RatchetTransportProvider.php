<?php

namespace Thruway\Transport;

use Ratchet\WebSocket\Version\RFC6455\Frame;
use Thruway\Event\NewConnectionEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Exception\DeserializationException;
use Thruway\Logging\Logger;
use Thruway\Serializer\JsonSerializer;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\WebSocket\WsServerInterface;
use React\Socket\Server as Reactor;

/**
 * Class RatchetTransportProvider
 *
 * @package Thruway\Transport
 */
class RatchetTransportProvider extends AbstractRouterTransportProvider implements MessageComponentInterface, WsServerInterface
{
    /**
     * @var string
     */
    private $address;

    /**
     * @var string|int
     */
    private $port;

    /**
     * @var \Ratchet\Server\IoServer
     */
    private $server;

    /**
     * @var \SplObjectStorage
     */
    private $transports;

    /**
     * Constructor
     *
     * @param string $address
     * @param string|int $port
     */
    public function __construct($address = "127.0.0.1", $port = 8080)
    {
        $this->port       = $port;
        $this->address    = $address;
        $this->transports = new \SplObjectStorage();
    }

    /**
     * Interface stuff
     */

    /** @inheritdoc */
    public function getSubProtocols()
    {
        return ['wamp.2.json'];
    }


    /** @inheritdoc */
    public function onOpen(ConnectionInterface $conn)
    {
        Logger::debug($this, "RatchetTransportProvider::onOpen");

        $transport = new RatchetTransport($conn, $this->loop);

        // this will need to be a little more dynamic at some point
        $transport->setSerializer(new JsonSerializer());

        $transport->setTrusted($this->trusted);

        $this->transports->attach($conn, $transport);

        $this->router->getEventDispatcher()->dispatch("new_connection", new NewConnectionEvent($transport));
    }

    /** @inheritdoc */
    public function onClose(ConnectionInterface $conn)
    {
        /* @var $transport RatchetTransport */
        $transport = $this->transports[$conn];

        $this->transports->detach($conn);

        $this->router->onClose($transport);

        Logger::info($this, "Ratchet has closed");
    }

    /** @inheritdoc */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Logger::error($this, "onError...");
        // TODO: Implement onError() method.
    }

    /** @inheritdoc */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        Logger::debug($this, "onMessage: ({$msg})");
        /** @var TransportInterface $transport */
        $transport = $this->transports[$from];

        try {
            $this->router->onMessage($transport, $transport->getSerializer()->deserialize($msg));
        } catch (DeserializationException $e) {
            Logger::alert($this, "Deserialization exception occurred.");
        } catch (\Exception $e) {
            Logger::alert($this, "Exception occurred during onMessage: " . $e->getMessage());
        }
    }

    /**
     * Handle on pong
     *
     * @param \Ratchet\ConnectionInterface $from
     * @param \Ratchet\WebSocket\Version\RFC6455\Frame $frame
     */
    public function onPong(ConnectionInterface $from, Frame $frame)
    {
        $transport = $this->transports[$from];

        if (method_exists($transport, 'onPong')) {
            $transport->onPong($frame);
        }
    }

    public function handleRouterStart(RouterStartEvent $event)
    {
        $ws = new WsServer($this);
        $ws->disableVersion(0);

        $socket = new Reactor($this->loop);
        $socket->listen($this->port, $this->address);

        Logger::info($this, "Websocket listening on " . $this->address . ":" . $this->port);

        $this->server = new IoServer(new HttpServer($ws), $socket, $this->loop);
    }

    public static function getSubscribedEvents()
    {
        return [
            "router.start" => ["handleRouterStart", 10]
        ];
    }
}
