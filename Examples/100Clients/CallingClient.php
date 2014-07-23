<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 7/22/14
 * Time: 4:01 PM
 */

class CallingClient extends \Thruway\Peer\Client {
    private $thePromise;

    function __construct($realm, $loop, $thePromise)
    {
        parent::__construct($realm, $loop);

        $this->thePromise = $thePromise;
    }

    public function onSessionStart($session, $transport) {
        $this->thePromise->then(function () use ($session) {
            $this->getCaller()->call($session, 'com.example.thefunction0', array())
                ->then(function ($res) {
                        var_dump($res);
                    });
        });
    }
} 