<?php


namespace Thruway\Event;


use Thruway\Transport\TransportInterface;

class NewConnectionEvent extends Event {
    /** @var  TransportInterface */
    public $transport;

    function __construct($transport)
    {
        $this->transport = $transport;
    }


} 