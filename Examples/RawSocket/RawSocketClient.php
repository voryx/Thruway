<?php

class RawSocketClient extends \Thruway\Peer\Client {
    public function onSessionStart($session, $transport) {
        $this->getCallee()->register($this->session, 'com.example.add2', [$this, 'add2'])
            ->then(
                function () {
                    echo "Registered RPC\n";

                    $this->getCaller()->call($this->session, 'com.example.add2', [2, 3])
                        ->then(
                            function ($res) {
                                echo "Got result: " . $res[0] . "\n";

                                $this->setAttemptRetry(false);
                                $this->session->shutdown();
                            }
                        );
                }
            );
    }

    public function add2($args) {
        return $args[0] + $args[1];
    }
} 