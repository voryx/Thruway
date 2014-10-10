<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 8/31/14
 * Time: 3:04 PM
 */

namespace Voryx\ThruwayBundle\Client;


use React\Promise\Deferred;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\User\User;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\TransportInterface;

class ClientManager
{

    /* @var Container */
    private $container;

    private $config;


    function __construct(Container $container, $config)
    {
        $this->container = $container;

        $this->config = $config;

    }

    /**
     * //@todo implement a non-blocking version of this
     *
     * @param $topicName
     * @param $arguments
     * @param array|null $argumentsKw
     * @param null $options
     * @return \React\Promise\Promise
     */
    public function publish($topicName, $arguments, $argumentsKw = [], $options = null)
    {
        //If we already have a client open that we can use, use that
        if ($this->container->initialized('voryx.thruway.connection')
            && $client = $this->container->get('voryx.thruway.connection')->getClient()
        ) {
            $session = $this->container->get('voryx.thruway.connection')->getSession();

            return $session->publish($topicName, $arguments, $argumentsKw, $options);
        }

        //If we don't already have a long running client, get a short lived one.
        $client = $this->getShortClient();
        $options['acknowledge'] = true;
        $deferrer = new Deferred();

        $client->on(
            "open",
            function (ClientSession $session, TransportInterface $transport) use (
                $deferrer,
                $topicName,
                $arguments,
                $argumentsKw,
                $options
            ) {
                $session->publish($topicName, $arguments, $argumentsKw, $options)->then(
                    function () use ($deferrer, $transport) {
                        $transport->close();
                        $deferrer->resolve();
                    }
                );
            }
        );

        $client->on(
            "error",
            function ($error) use ($topicName) {
                $this->container->get('logger')->addError(
                    "Got the following error when trying to publish to '{$topicName}': {$error}"
                );
//                throw new \Exception("Got the following error when trying to publish to '{$topicName}': {$error}");
            }
        );

        $client->start();

        return $deferrer->promise();

    }

    /**
     * @param $procedureName
     * @param $arguments
     * @return \React\Promise\Promise
     */
    public function call($procedureName, $arguments)
    {
        //If we already have a client open that we can use, use that
        if ($this->container->initialized('voryx.thruway.connection')
            && $client = $this->container->get('voryx.thruway.connection')->getClient()
        ) {
            $session = $this->container->get('voryx.thruway.connection')->getSession();

            return $session->call($procedureName, $arguments);
        }

        //If we don't already have a long running client, get a short lived one.
        $client = $this->getShortClient();
        $options['acknowledge'] = true;
        $deferrer = new Deferred();

        $client->on(
            "open",
            function (ClientSession $session, TransportInterface $transport) use (
                $deferrer,
                $procedureName,
                $arguments
            ) {
                $session->call($procedureName, $arguments)->then(
                    function ($res) use ($deferrer, $transport) {
                        $transport->close();
                        $deferrer->resolve($res);
                    }
                );
            }
        );

        $client->on(
            "error",
            function ($error) use ($procedureName) {
                $this->container->get('logger')->addError(
                    "Got the following error when trying to call '{$procedureName}': {$error}"
                );
                throw new \Exception("Got the following error when trying to call '{$procedureName}': {$error}");
            }
        );
        $client->start();

        return $deferrer->promise();

    }


    private function getShortClient()
    {

        /* @var $user \Symfony\Component\Security\Core\User\User */
        $user = $this->container->get('security.context')->getToken()->getUser();
        $client = new Client($this->config['realm']);
        $client->setAttemptRetry(false);
        $client->addTransportProvider(
            new \Thruway\Transport\PawlTransportProvider("ws://{$this->config['server']}:{$this->config['port']}")
        );

        if ($user instanceof User) {
            $client->setAuthId($user->getUsername());
            $client->addClientAuthenticator(new \Thruway\ClientWampCraAuthenticator($user->getUsername(), $user->getPassword()));
        }

        return $client;

    }
} 