<?php


namespace Thruway\Module;


use React\EventLoop\LoopInterface;
use Thruway\Peer\Client;
use Thruway\Peer\RouterInterface;

class ModuleClient extends Client implements ModuleInterface {
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
}