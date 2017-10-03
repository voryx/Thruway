<?php

namespace Thruway\Module;

use React\EventLoop\LoopInterface;
use Thruway\Peer\Client;
use Thruway\Peer\RouterInterface;

/**
 * Class RouterModuleClient
 * @package Thruway\Module
 */
class RouterModuleClient extends Client implements RouterModuleInterface
{
    /** @var  RouterInterface */
    protected $router;

    /**
     * Called by the router when it is added
     *
     * @param RouterInterface $router
     * @param LoopInterface $loop
     */
    public function initModule(RouterInterface $router, LoopInterface $loop)
    {
        $this->router = $router;
        $this->setLoop($loop);

        $this->router->addInternalClient($this);
    }

    /**
     * If people don't want to implement this
     *
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [];
    }
}
