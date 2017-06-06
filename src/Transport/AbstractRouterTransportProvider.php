<?php


namespace Thruway\Transport;


use Thruway\Module\RouterModule;

abstract class AbstractRouterTransportProvider extends RouterModule implements RouterTransportProviderInterface {
    /**
     * @var \Thruway\Peer\RouterInterface
     */
    protected $router;

    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;

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