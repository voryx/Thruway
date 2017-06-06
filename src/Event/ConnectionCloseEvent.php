<?php


namespace Thruway\Event;


use Thruway\Session;

class ConnectionCloseEvent extends Event {
    /** @var  Session */
    public $session;

    function __construct($session)
    {
        $this->session = $session;
    }
}