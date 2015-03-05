<?php

namespace Thruway\Transport;

use Ratchet\WebSocket\Version\RFC6455\Frame;
use Thruway\Exception\DeserializationException;
use Thruway\Logging\Logger;
use Thruway\Peer\PeerInterface;
use Thruway\Peer\RouterInterface;
use Thruway\Serializer\JsonSerializer;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\WebSocket\WsServerInterface;
use React\EventLoop\LoopInterface;
use React\Socket\Server as Reactor;

/**
 * Class RatchetTransportProvider
 *
 * @package Thruway\Transport
 */
class RatchetTransportProvider extends AbstractTransportProvider implements MessageComponentInterface, WsServerInterface
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
        $this->peer       = null;
        $this->port       = $port;
        $this->address    = $address;
        $this->transports = new \SplObjectStorage();
    }

    public function initModule(RouterInterface $router, LoopInterface $loop) {

    }

    /**
     * Start transportprovider
     *
     * @param \Thruway\Peer\PeerInterface $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(PeerInterface $peer, LoopInterface $loop)
    {
        $this->peer = $peer;
        $this->loop = $loop;

        $ws = new WsServer($this);
        $ws->disableVersion(0);

        $socket = new Reactor($this->loop);
        $socket->listen($this->port, $this->address);

        Logger::info($this, "Listening on " . $this->address . ":" . $this->port);

        $this->server = new IoServer(new HttpServer($ws), $socket, $this->loop);
    }


    /**
     * Interface stuff
     */

    /**
     * If any component in a stack supports a WebSocket sub-protocol return each supported in an array
     *
     * @return array
     * @temporary This method may be removed in future version (note that will not break code, just make some code obsolete)
     */
    public function getSubProtocols()
    {
        return ['wamp.2.json'];
    }


    /**
     * When a new connection is opened it will be passed to this method
     *
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn)
    {
        Logger::debug($this, "RatchetTransportProvider::onOpen");

        $transport = new RatchetTransport($conn, $this->loop);

        // this will need to be a little more dynamic at some point
        $transport->setSerializer(new JsonSerializer());

        $transport->setTrusted($this->trusted);

        $this->transports->attach($conn, $transport);

        $this->peer->onOpen($transport);

//        $session = new Session($conn);
//
//        $this->sessions->attach($conn, $session);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).
     * SendMessage to $conn will not result in an error if it has already been closed.
     *
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn)
    {
        /* @var $transport RatchetTransport */
        $transport = $this->transports[$conn];

        $this->transports->detach($conn);

        $this->peer->onClose($transport);

        Logger::info($this, "Ratchet has closed");
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application
     * where an Exception is thrown, the Exception is sent back down the stack,
     * handled by the Server and bubbled back up the application through this method
     *
     * @param  \Ratchet\ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        Logger::error($this, "onError...");
        // TODO: Implement onError() method.
    }

    /**
     * Triggered when a client sends data through the socket
     *
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        Logger::debug($this, "onMessage: ({$msg})");
        /** @var TransportInterface $transport */
        $transport = $this->transports[$from];

        try {
            $this->peer->onMessage($transport, $transport->getSerializer()->deserialize($msg));
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

}
