<?php


namespace Voryx\ThruwayBundle;


use React\EventLoop\LoopInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Voryx\ThruwayBundle\Client\ClientManager;

class ContainerFactory
{

    public static function createContainer($containerName, ClientManager $thruwayClient, LoopInterface $loop)
    {

        /** @var ContainerInterface $container */
        $container = new $containerName();

        //These services will be passed from the outer container into the inner container
        $container->set('thruway.client', $thruwayClient);
        $container->set('voryx.thruway.loop', $loop);

        return $container;
    }

}