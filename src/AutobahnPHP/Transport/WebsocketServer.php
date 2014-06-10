<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 5:06 PM
 */

namespace AutobahnPHP\Transport;


use AutobahnPHP\Peer;
use React\EventLoop\Factory;
use React\Socket\Server;

class WebsocketServer extends AbstractTransport {
    private $hostname;
    private $port;

    private $socket;

    private $peer;

    private $loop;

    /**
     * @var \SplObjectStorage
     */
    private $connections;

    function __construct(Peer $peer, $hostname = "127.0.0.1", $port = 8080, $loop = null)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->peer = $peer;

        $this->connections = new \SplObjectStorage();

        // create a loop if we weren't passed one
        if ($loop === null) {
            $this->loop = Factory::create();
        }

        $this->socket = new Server($this->loop);

        $this->socket->on('connection', array($this, 'onConnect'));

    }

    /**
     * startTransport is called by the Peer to let it know that
     * it is ready for the transport to be brought up
     */
    function startTransport() {
        $this->socket->listen($this->port, $this->hostname);
        $this->loop->run();
    }

    function onConnect($conn) {
        echo "Connect...\n";
        $conn->on('close', array($this, 'onClose'));
        $conn->on('data', array($this, 'onData'));

    }

    function onClose($conn) {
        echo "Close...\n";
    }

    function onData($data, $conn) {

    }
}