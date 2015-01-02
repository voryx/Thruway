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
     * @return RouterInterface
     */
    public function getRouter();

    /**
     * @param RouterInterface $router
     */
    public function setRouter(RouterInterface $router);

    /**
     * @return LoopInterface
     */
    public function getLoop();

    /**
     * @param LoopInterface $loop
     */
    public function setLoop(LoopInterface $loop);

    /**
     * Gets called when the module is initialized in the router
     *
     */
    public function onInitialize();

}