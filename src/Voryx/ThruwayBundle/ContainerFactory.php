<?php


namespace Voryx\ThruwayBundle;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Voryx\ThruwayBundle\Client\ClientManager;

class ContainerFactory
{

    public static function createContainer($containerName, ClientManager $thruwayClient)
    {

        /** @var ContainerInterface $container */
        $container = new $containerName();
        $container->set('thruway.client', $thruwayClient);

        return $container;
    }

}