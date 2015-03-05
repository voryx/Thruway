<?php

namespace Thruway\Module;


use React\EventLoop\LoopInterface;
use Thruway\Peer\Client;
use Thruway\Peer\Router;
use Thruway\Peer\RouterInterface;

/**
 * Class Module
 * @package Thruway\Module
 */
class Module extends Client implements ModuleInterface
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * @param LoopInterface $loop
     */
    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Gets called when the module is initialized in the router
     */
    public function onInitialize()
    {
        // TODO: Implement onInitialize() method.
    }
}
