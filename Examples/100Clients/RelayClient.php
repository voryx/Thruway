<?php

/**
 * Class RelayClient
 */
class RelayClient extends \Thruway\Peer\Client
{
    /**
     * @var int
     */
    private $number;

    /**
     * @var \React\Promise\Deferred
     */
    private $registeredDeferred;

    /**
     * Constructor
     * 
     * @param string $realm
     * @param \React\EventLoop\LoopInterface $loop
     * @param int $number
     */
    public function __construct($realm, $loop, $number)
    {
        parent::__construct($realm, $loop);

        $this->number = $number;

        $this->registeredDeferred = new \React\Promise\Deferred();
    }

    /**
     * Handle for RPC 'com.example.thefunction{$number}'
     * 
     * @return \React\Promise\Promise
     */
    public function theFunction()
    {
        $futureResult = new \React\Promise\Deferred();

        $this->session->call('com.example.thefunction' . ($this->number + 1), [])
            ->then(function ($res) use ($futureResult) {
                if (is_scalar($res[0])) {
                    $res[0] = $res[0] . ".";
                }
                $futureResult->resolve($res[0]);
            });

        return $futureResult->promise();
    }

    /**
     * Handle on session start
     * 
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $session->register('com.example.thefunction' . $this->number, [$this, 'theFunction'])
            ->then(function () {
                $this->registeredDeferred->resolve();
            });
    }

    /**
     * Get registered promise
     * 
     * @return \React\Promise\Deferred
     */
    public function getRegisteredPromise()
    {
        return $this->registeredDeferred->promise();
    }

} 