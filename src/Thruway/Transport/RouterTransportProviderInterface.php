<?php


namespace Thruway\Transport;


interface RouterTransportProviderInterface extends TransportProviderInterface {
    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted);
}