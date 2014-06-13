<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 3:47 PM
 */

namespace AutobahnPHP\Transport;

use AutobahnPHP\Peer\AbstractPeer;
use AutobahnPHP\Session;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;

class RatchetServer implements MessageComponentInterface, WsServerInterface {
    /**
     * If any component in a stack supports a WebSocket sub-protocol return each supported in an array
     * @return array
     * @temporary This method may be removed in future version (note that will not break code, just make some code obsolete)
     */
    function getSubProtocols()
    {
        return array('wamp.2.json');
    }

    /**
     * @var AbstractPeer
     */
    private $peer;

    /**
     * @var \SplObjectStorage
     */
    private $sessions;


    function __construct(AbstractPeer $peer)
    {
        $this->peer = $peer;
        $this->sessions = new \SplObjectStorage();
    }


    /**
     * When a new connection is opened it will be passed to this method
     * @param  ConnectionInterface $conn The socket/connection that just connected to your application
     * @throws \Exception
     */
    function onOpen(ConnectionInterface $conn)
    {
        echo "onOpen...\n";

        $session = new Session($conn);

        // TODO: add transport auth stuff to the session

        $this->sessions->attach($conn, $session);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     * @param  ConnectionInterface $conn The socket/connection that is closing/closed
     * @throws \Exception
     */
    function onClose(ConnectionInterface $conn)
    {
        $session = $this->sessions[$conn];

        $this->sessions->detach($conn);

        echo "onClose...\n";
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method
     * @param  ConnectionInterface $conn
     * @param  \Exception $e
     * @throws \Exception
     */
    function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "onError...\n";
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
        echo "onMessage...(" . $msg . "\n";
        $session = $this->sessions[$from];

        $this->peer->onRawMessage($session, $msg);
    }

} 