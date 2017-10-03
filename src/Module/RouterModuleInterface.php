<?php

namespace Thruway\Module;

use React\EventLoop\LoopInterface;
use Thruway\Event\EventSubscriberInterface;
use Thruway\Peer\RouterInterface;

/**
 * Interface RouterModuleInterface
 * @package Thruway\RouterModule
 */
interface RouterModuleInterface extends EventSubscriberInterface
{
    /**
     * Called by the router when it is added
     *
     * @param RouterInterface $router
     * @param LoopInterface $loop
     */
    public function initModule(RouterInterface $router, LoopInterface $loop);

    /**
     * @return LoopInterface
     */
    public function getLoop();
}
