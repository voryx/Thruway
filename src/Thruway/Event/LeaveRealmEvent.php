<?php


namespace Thruway\Event;


class LeaveRealmEvent extends Event {
    public $session;
    public $realm;

    function __construct($realm, $session)
    {
        $this->realm   = $realm;
        $this->session = $session;
    }
}