<?php

namespace Voryx\ThruwayBundle\Supervisor;


use Symfony\Component\DependencyInjection\Container;
use Thruway\ClientSession;
use Thruway\Peer\Client;

/**
 * Long running thruway client that handles congestion related events from the router.
 *
 * Class ProcessManager
 * @package Voryx\ThruwayBundle\Supervisor
 */
class ProcessManager extends Client
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
            $supervisor = $this->container->get('voryx.thruway.supervisor');
            $workerName = $this->container->get('voryx.thruway.resource.mapper')->findWorker($args[0]["name"]);

            $processes = $supervisor->getAllProcessInfo();

            foreach ($processes as $process) {
                if (strpos($process['name'], $workerName) === 0 && $process['statename'] !== "RUNNING") {
                    $supervisor->startProcess("thruway:{$process['name']}");
                    break;
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}