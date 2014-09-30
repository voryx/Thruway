<?php

namespace Thruway\Transport;

use Ratchet\WebSocket\Version\RFC6455\Frame;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Peer\AbstractPeer;
use Thruway\Serializer\JsonSerializer;
use Thruway\Session;
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
     * @var \Thruway\Peer\AbstractPeer
     */
    private $peer;

    /**
     * @var string
     */
    private $address;

    /**
     * @var string|int
     */
    private $port;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    private $loop;

    /**
     * @var \Ratchet\Server\IoServer
     */
    private $server;

    /**
     * @var \SplObjectStorage
     */
    private $transports;

    /**
     * @var ManagerInterface
     */
    private $manager;

    /**
     * Constructor
     *
     * @param string $address
     * @param string|int $port
     */
    function __construct($address = "127.0.0.1", $port = 8080)
    {
        $this->peer       = null;
        $this->port       = $port;
        $this->address    = $address;
        $this->transports = new \SplObjectStorage();
        $this->manager    = new ManagerDummy();
    }

    /**
     * Start transportprovider
     *
     * @param AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        $this->peer = $peer;
        $this->loop = $loop;

        $ws = new WsServer($this);
        $ws->disableVersion(0);

        $socket = new Reactor($this->loop);
        $socket->listen($this->port, $this->address);

        $this->server = new IoServer(new HttpServer($ws), $socket, $this->loop);
    }


    /**
     * Interface stuff
     */

    /**
     * If any component in a stack supports a WebSocket sub-protocol return each supported in an array
     * @return array
     * @temporary This method may be removed in future version (note that will not break code, just make some code obsolete)
     */
    function getSubProtocols()
    {
        return ['wamp.2.json'];
    }


    /**
     * When a new connection is opened it will be passed to this method
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        $this->manager->debug("RatchetTransportProvider::onOpen");

        $transport = new RatchetTransport($conn, $this->loop);

        // this will need to be a little more dynamic at some point
        $transport->setSerializer(new JsonSerializer());

        $this->transports->attach($conn, $transport);

        $this->peer->onOpen($transport);

//        $session = new Session($conn);
//
//        $this->sessions->attach($conn, $session);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  \Ratchet\ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        /* @var $transport RatchetTransport */
        $transport = $this->transports[$conn];

        $this->transports->detach($conn);

        $this->peer->onClose($transport);

        $this->manager->debug("onClose...");
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  \Ratchet\ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->manager->error("onError...");
        // TODO: Implement onError() method.
    }

    /**
     * Triggered when a client sends data through the socket
     * @param  \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param  string $msg The message received
     * @throws \Exception
     */
    function onMessage(ConnectionInterface $from, $msg)
    {
        $this->manager->debug("onMessage...({$msg})");
        /** @var TransportInterface $transport */
        $transport = $this->transports[$from];

        try {
            $this->peer->onMessage($transport, $transport->getSerializer()->deserialize($msg));
        } catch (\Exception $e) {
            $this->manager->warning("Deserialization exception occurred.");
        }
    }

    /**
     * Handle on pong
     *
     * @param \Ratchet\ConnectionInterface $from
     * @param \Ratchet\WebSocket\Version\RFC6455\Frame $frame
     */
    function onPong(ConnectionInterface $from, Frame $frame)
    {
        $transport = $this->transports[$from];

        if (method_exists($transport, 'onPong')) {
            $transport->onPong($frame);
        }
    }

    /**
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;

        $this->manager->info("Manager attached to RatchetTransportProvider");
    }

    /**
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

} 
