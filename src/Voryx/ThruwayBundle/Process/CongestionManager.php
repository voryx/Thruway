<?php

namespace Voryx\ThruwayBundle\Process;


use Symfony\Component\DependencyInjection\Container;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Voryx\ThruwayBundle\Process\Process;

/**
 * Long running thruway client that handles congestion related events from the router.
 *
 * Class ProcessManager
 */
class CongestionManager extends Client
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @param string $realm
     * @param Container $container
     */
    function __construct($realm, $loop, Container $container)
    {
        $this->container = $container;

        $this->on('open', function (ClientSession $session) {
            $session->subscribe("thruway.metaevent.procedure.congestion", [$this, "onCongestion"]);
        });

        parent::__construct($realm, $loop);

    }

    /**
     * @param $args
     */
    public function onCongestion($args)
    {

        try {
            $worker    = $this->container->get('voryx.thruway.resource.mapper')->findWorker($args[0]->name);
            $env       = $this->container->get('kernel')->getEnvironment();
            $phpBinary = PHP_BINARY;
            $loop      = $this->container->get('voryx.thruway.loop');

            $cmd = "{$phpBinary} {$this->container->get('kernel')->getRootDir()}/console --env={$env} thruway:process add {$worker}";

            $process = new Process($cmd);
            $process->start($loop);

        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

}
