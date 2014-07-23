<?php

class RelayClient extends \Thruway\Peer\Client {
    private $number;

    private $registeredDeferred;

    function __construct($realm, $loop, $number)
    {
        parent::__construct($realm, $loop);

        $this->number = $number;

        $this->registeredDeferred = new \React\Promise\Deferred();
    }

    public function theFunction() {
        $futureResult = new \React\Promise\Deferred();

        $this->getCaller()->call($this->session, 'com.example.thefunction' . ($this->number + 1), array())
            ->then(function ($res) use ($futureResult) {
                    $res[0] = $res[0] . ".";
                    $futureResult->resolve($res);
                });

        return $futureResult->promise();
    }

    public function onSessionStart($session, $transport) {
        $this->getCallee()->register($session, 'com.example.thefunction' . $this->number, array($this, 'theFunction'))
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