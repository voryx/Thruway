<?php

namespace Thruway\Transport;

use Thruway\Exception\DeserializationException;
use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Peer\AbstractPeer;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use Thruway\Serializer\JsonSerializer;

/**
 * Class WebsocketClient
 *
 * @package Thruway\Transport
 */
class PawlTransportProvider extends AbstractTransportProvider implements TransportProviderInterface
{

    /**
     * @var string
     */
    private $URL;

    /**
     * @var \Ratchet\Client\Factory
     */
    private $connector;

    /**
     * Constructor
     *
     * @param string $URL
     */
    function __construct($URL = "ws://127.0.0.1:9090/")
    {
        $this->peer    = null;
        $this->URL     = $URL;
        $this->manager = new ManagerDummy();
    }

    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\AbstractPeer $peer
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(AbstractPeer $peer, LoopInterface $loop)
    {
        Logger::info($this, "Starting Transport");

        $this->peer      = $peer;
        $this->loop      = $loop;
        $this->connector = new \Ratchet\Client\Factory($this->loop);

        $this->connector->__invoke($this->URL, ['wamp.2.json'])->then(
            function (WebSocket $conn) {

                Logger::info($this, "Pawl has connected");

                $transport = new PawlTransport($conn, $this->loop);
                $transport->setSerializer(new JsonSerializer());
                $transport->setTrusted($this->trusted);

                $this->peer->onOpen($transport);

                $conn->on(
                    'message',
                    function ($msg) use ($transport) {
                        Logger::debug($this, "Received: {$msg}");
                        try {
                            $this->peer->onMessage($transport, $transport->getSerializer()->deserialize($msg));
                        } catch (DeserializationException $e) {
                            Logger::warning($this, "Deserialization exception occurred.");
                        } catch (\Exception $e) {
                            Logger::warning($this, "Exception occurred during onMessage: " . $e->getMessage());
                        }
                    }
                );

                $conn->on(
                    'close',
                    function ($conn) {
                        Logger::info($this, "Pawl has closed");
                        $this->peer->onClose('close');
                    }
                );

                $conn->on(
                    'pong',
                    function ($frame, $ws) use ($transport) {
                        $transport->onPong($frame, $ws);
                    }
                );
            },
            function ($e) {
                $this->peer->onClose('unreachable');
                Logger::info($this, "Could not connect: {$e->getMessage()}");
                // $this->loop->stop();
            }
        );
    }

    /**
     * Get peer
     *
     * @return \Thruway\Peer\AbstractPeer
     */
    public function getPeer()
    {
        return $this->peer;
    }

    /**
     * Set peer
     *
     * @param \Thruway\Peer\AbstractPeer $peer
     */
    public function setPeer(AbstractPeer $peer)
    {
        $this->peer = $peer;
    }

}
