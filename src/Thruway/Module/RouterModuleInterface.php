<?php


namespace Thruway\Module;


use React\EventLoop\LoopInterface;
use Thruway\Peer\RouterInterface;

/**
 * Interface RouterModuleInterface
 * @package Thruway\RouterModule
 */
interface RouterModuleInterface
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