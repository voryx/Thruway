<?php

/**
 * Class RawSocketClient
 */
class RawSocketClient extends \Thruway\Peer\Client
{
    /**
     * Handle on session start
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $session->register('com.example.add2', [$this, 'add2'])
            ->then(function () use ($session) {
                echo "Registered RPC\n";

                $session->call('com.example.add2', [2, 3])
                    ->then(function ($res) {
                        echo "Got result: " . $res[0] . "\n";

                        $this->setAttemptRetry(false);
                        $this->session->shutdown();
                    });
            });
    }

    /**
     *
     * @param array $args
     * @return mixed
     */
    public function add2($args)
    {
        return $args[0] + $args[1];
    }

}
