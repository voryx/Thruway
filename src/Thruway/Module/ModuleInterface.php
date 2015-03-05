<?php


namespace Thruway\Module;


use React\EventLoop\LoopInterface;
use Thruway\Peer\RouterInterface;

/**
 * Interface ModuleInterface
 * @package Thruway\Module
 */
interface ModuleInterface
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