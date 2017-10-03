<?php

namespace Thruway\Transport;

use Thruway\Module\RouterModule;

abstract class AbstractRouterTransportProvider extends RouterModule implements RouterTransportProviderInterface
{
    /**
     * @param boolean $trusted
     */
    public function setTrusted($trusted)
    {
        $this->trusted = $trusted;
    }

    /**
     * @var boolean
     */
    protected $trusted;
}
