<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 5:06 PM
 */

namespace AutobahnPHP\Transport;


use AutobahnPHP\ClientSession;
use AutobahnPHP\Peer\AbstractPeer;
use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Ratchet\Client\WebSocket;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;

/**
 * Class WebsocketClient
 * @package AutobahnPHP\Transport
 */
class WebsocketClient extends AbstractTransport implements EventEmitterInterface
{
    use EventEmitterTrait;

    /**
     * @var AbstractPeer
     */
    private $peer;

    /**
     * @var string
     */
    private $URL;

    /**
     * @var \React\EventLoop\ExtEventLoop|\React\EventLoop\LibEventLoop|\React\EventLoop\LibEvLoop|\React\EventLoop\StreamSelectLoop
     */
    private $loop;

    /**
     * @var \Ratchet\Client\Factory
     */
    private $connector;

    /**
     * @var null
     */
    private $session;

    /**
     * @param string $URL
     * @param AbstractPeer $peer
     */
    function __construct($URL = "ws://127.0.0.1:9090/", AbstractPeer $peer)
    {

        $this->peer = $peer;

        $this->URL = $URL;

        $this->loop = Factory::create();
        $this->connector = new \Ratchet\Client\Factory($this->loop);

        $this->session = null;

    }

    /**
     *
     */
    function startTransport()
    {
        echo "Starting Transport\n";

        $this->connector->__invoke($this->URL)->then(
            function (WebSocket $conn) {
                $this->session = new ClientSession($conn, $this->peer);
                $this->emit('connect', array($this->session));

                $conn->on(
                    'message',
                    function ($msg) {
                        echo "Received: {$msg}\n";
                        $this->peer->onRawMessage($this->session, $msg);
                    }
                );

                $conn->on(
                    'close',
                    function ($conn) {
                        $this->emit('close', array("closed"));
                    }
                );
            },
            function ($e) {
                $this->emit('close', array("unreachable"));
                echo "Could not connect: {$e->getMessage()}\n";
                $this->loop->stop();
            }
        );

        $this->loop->run();
    }


    /**
     * @return AbstractPeer
     */
    public function getPeer()
    {
        return $this->peer;
    }


    /**
     * @param AbstractPeer $peer
     */
    public function setPeer(AbstractPeer $peer)
    {
        $this->peer = $peer;
    }
}