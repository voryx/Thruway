<?php

namespace Thruway\Transport;

use Thruway\Exception\DeserializationException;
use Thruway\Logging\Logger;
use Ratchet\Client\WebSocket;
use React\EventLoop\LoopInterface;
use Thruway\Peer\ClientInterface;
use Thruway\Serializer\JsonSerializer;

/**
 * Class WebsocketClient
 *
 * @package Thruway\Transport
 */
class PawlTransportProvider extends AbstractClientTransportProvider
{
    /**
     * @var string
     */
    private $URL;

    /**
     * Constructor
     *
     * @param string $URL
     */
    public function __construct($URL = "ws://127.0.0.1:8080/")
    {
        $this->URL     = $URL;
    }

    /**
     * Start transport provider
     *
     * @param \Thruway\Peer\ClientInterface $client
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function startTransportProvider(ClientInterface $client, LoopInterface $loop)
    {
        Logger::info($this, "Starting Transport");

        $this->client    = $client;
        $this->loop      = $loop;

        \Ratchet\Client\connect($this->URL, ['wamp.2.json'], [], $loop)->then(
            function (WebSocket $conn) {
                Logger::info($this, "Pawl has connected");

                $transport = new PawlTransport($conn, $this->loop);
                $transport->setSerializer(new JsonSerializer());

                $this->client->onOpen($transport);

                $conn->on(
                    'message',
                    function ($msg) use ($transport) {
                        Logger::debug($this, "Received: {$msg}");
                        try {
                            $this->client->onMessage($transport, $transport->getSerializer()->deserialize($msg));
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
                        $this->client->onClose('close');
                    }
                );
            },
            function ($e) {
                $this->client->onClose('unreachable');
                Logger::info($this, "Could not connect: {$e->getMessage()}");
            }
        );
    }
}
