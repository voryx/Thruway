<?php

namespace Thruway\Transport;

use Thruway\Module\RouterModuleInterface;

interface RouterTransportProviderInterface extends TransportProviderInterface, RouterModuleInterface
{
    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted);
}
