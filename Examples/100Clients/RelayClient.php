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
     * @param string $realm
     * @param \React\EventLoop\LoopInterface $loop
     * @param $number
     */
    function __construct($realm, $loop, $number)
    {
        parent::__construct($realm, $loop);

        $this->number = $number;

        $this->registeredDeferred = new \React\Promise\Deferred();
    }

    /**
     * @return \React\Promise\Promise
     */
    public function theFunction()
    {
        $futureResult = new \React\Promise\Deferred();

        $this->getCaller()->call($this->session, 'com.example.thefunction' . ($this->number + 1), [])
            ->then(function ($res) use ($futureResult) {
                $res[0] = $res[0] . ".";
                $futureResult->resolve($res);
            });

        return $futureResult->promise();
    }

    /**
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $this->getCallee()->register($session, 'com.example.thefunction' . $this->number, [$this, 'theFunction'])
            ->then(function () {
                $this->registeredDeferred->resolve();
            });
    }

    /**
     * @return \React\Promise\Deferred
     */
    public function getRegisteredPromise()
    {
        return $this->registeredDeferred->promise();
    }

} 