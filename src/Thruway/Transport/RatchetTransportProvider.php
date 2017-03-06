<?php

namespace Thruway\Transport;

use Ratchet\RFC6455\Messaging\Frame;
use React\EventLoop\LoopInterface;
use Thruway\Event\ConnectionCloseEvent;
use Thruway\Event\ConnectionOpenEvent;
use Thruway\Event\RouterStartEvent;
use Thruway\Event\RouterStopEvent;
use Thruway\Exception\DeserializationException;
use Thruway\Logging\Logger;
use Thruway\Message\HelloMessage;
use Thruway\Serializer\JsonSerializer;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\WebSocket\WsServerInterface;
use React\Socket\Server as Reactor;
use Thruway\Session;

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
    private $sessions;

    /**
     * @var WsServer
     */
    private $ws;

    /**
     * Constructor
     *
     * @param string $address
     * @param string|int $port
     */
    public function __construct($address = "127.0.0.1", $port = 8080)
    {
        $this->port     = $port;
        $this->address  = $address;
        $this->sessions = new \SplObjectStorage();
        $this->ws       = new WsServer($this);
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

        $session = $this->router->createNewSession($transport);

        $this->sessions->attach($conn, $session);

        $this->router->getEventDispatcher()->dispatch("connection_open", new ConnectionOpenEvent($session));
    }

    /** @inheritdoc */
    public function onClose(ConnectionInterface $conn)
    {
        /** @var Session $session */
        $session = $this->sessions[$conn];

        $this->sessions->detach($conn);

        $this->router->getEventDispatcher()->dispatch('connection_close', new ConnectionCloseEvent($session));

        unset($this->sessions[$conn]);

        Logger::info($this, "Ratchet has closed");
    }

    /** @inheritdoc */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Logger::error($this, "onError...");
    }

    /** @inheritdoc */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        Logger::debug($this, "onMessage: ({$msg})");
        /** @var Session $session */
        $session = $this->sessions[$from];

        try {
            //$this->router->onMessage($transport, $transport->getSerializer()->deserialize($msg));
            $msg = $session->getTransport()->getSerializer()->deserialize($msg);

            if ($msg instanceof HelloMessage) {

                $details = $msg->getDetails();

                $details->transport = (object) $session->getTransport()->getTransportDetails();

                $msg->setDetails($details);
            }

            $session->dispatchMessage($msg);
        } catch (DeserializationException $e) {
            Logger::alert($this, "Deserialization exception occurred.");
        } catch (\Exception $e) {
            Logger::alert($this, "Exception occurred during onMessage: ".$e->getMessage());
        }
    }

    /**
     * Handle on pong
     *
     * @param \Ratchet\ConnectionInterface $from
     * @param Frame $frame
     */
    public function onPong(ConnectionInterface $from, Frame $frame)
    {
        $transport = $this->sessions[$from];

        if (method_exists($transport, 'onPong')) {
            $transport->onPong($frame);
        }
    }

    public function handleRouterStart(RouterStartEvent $event)
    {

        $socket = new Reactor('tcp://' . $this->address . ':' . $this->port, $this->loop);

        Logger::info($this, "Websocket listening on ".$this->address.":".$this->port);

        $this->server = new IoServer(new HttpServer($this->ws), $socket, $this->loop);
    }

    public function enableKeepAlive(LoopInterface $loop, $interval = 30)
    {
        $this->ws->enableKeepAlive($loop, $interval);
    }

    public function handleRouterStop(RouterStopEvent $event)
    {
        if ($this->server) {
            $this->server->socket->close();
        }

        foreach ($this->sessions as $k) {
            $this->sessions[$k]->shutdown();
        }
    }

    public static function getSubscribedEvents()
    {
        return [
          "router.start" => ["handleRouterStart", 10],
          "router.stop"  => ["handleRouterStop", 10]
        ];
    }
}
