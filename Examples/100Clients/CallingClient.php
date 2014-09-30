<?php

/**
 * Class CallingClient
 */
class CallingClient extends \Thruway\Peer\Client
{
    /**
     * @var
     */
    private $thePromise;

    /**
     * @param string $realm
     * @param \React\EventLoop\LoopInterface $loop
     * @param $thePromise
     */
    function __construct($realm, $loop, $thePromise)
    {
        parent::__construct($realm, $loop);

        $this->thePromise = $thePromise;
    }

    /**
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $this->thePromise->then(function () use ($session) {
            $this->getCaller()->call($session, 'com.example.thefunction0', [])
                ->then(function ($res) {
                    var_dump($res);
                });
        });
    }
} 