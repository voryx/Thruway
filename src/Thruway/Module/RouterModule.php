<?php

namespace Thruway\Module;


use React\EventLoop\LoopInterface;
use Thruway\Peer\RouterInterface;

/**
 * Class RouterModule
 * @package Thruway\RouterModule
 */
class RouterModule implements RouterModuleInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @param RouterInterface $router
     * @param LoopInterface $loop
     */
    public function initModule(RouterInterface $router, LoopInterface $loop)
    {
        $this->router = $router;
        $this->loop = $loop;
    }

    public function getLoop() {
        return $this->loop;
    }
}
