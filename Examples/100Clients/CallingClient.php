<?php

/**
 * Class CallingClient
 */
class CallingClient extends \Thruway\Peer\Client
{
    /**
     * @var \React\Promise\Promise
     */
    private $thePromise;

    /**
     * Constructor
     *
     * @param string $realm
     * @param \React\EventLoop\LoopInterface $loop
     * @param \React\Promise\Promise $thePromise
     */
    public function __construct($realm, $loop, $thePromise)
    {
        parent::__construct($realm, $loop);

        $this->thePromise = $thePromise;
    }

    /**
     * Handle on session start
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $this->thePromise->then(function () use ($session) {
            $session->call('com.example.thefunction0', [])
                ->then(function ($res) {
                    echo "Done.\n";
                    exit;
                });
        });
    }
} 