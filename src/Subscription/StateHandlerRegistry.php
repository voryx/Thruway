<?php

namespace Thruway\Subscription;

use React\EventLoop\LoopInterface;
use Thruway\Logging\Logger;
use Thruway\Message\SubscribeMessage;
use Thruway\Module\RouterModuleClient;
use Thruway\Peer\RouterInterface;
use Thruway\Realm;
use Thruway\Role\Broker;

/**
 * Class StateHandlerRegistry
 * @package Thruway\Subscription
 */
class StateHandlerRegistry extends RouterModuleClient
{
    /**
     * @var boolean
     */
    private $ready;

    /**
     * @var array
     */
    private $stateHandlerRegistrations = [];

    /**
     * @var \SplObjectStorage
     */
    private $stateHandlerMap;

    /**
     * @var Broker
     */
    private $broker;

    /**
     * @var Realm
     */
    private $routerRealm;

    /**
     * @param string $realm
     * @param LoopInterface $loop
     */
    public function __construct($realm, LoopInterface $loop = null)
    {
        $this->stateHandlerMap = new \SplObjectStorage();

        $this->setReady(false);

        parent::__construct($realm, $loop);
    }

    /**
     * Gets called when the module is initialized in the router
     *
     * @inheritdoc
     */
    public function initModule(RouterInterface $router, LoopInterface $loop)
    {
        parent::initModule($router, $loop);

        $this->routerRealm = $router->getRealmManager()->getRealm($this->getRealm());
        $this->broker      = $this->routerRealm->getBroker();
        $this->broker->setStateHandlerRegistry($this);
    }

    /**
     * Handles session started
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportProviderInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $promises   = [];
        $promises[] = $session->register('add_state_handler', [$this, "addStateHandler"]);
        $promises[] = $session->register('remove_state_handler', [$this, "removeStateHandler"]);

        $pAll = \React\Promise\all($promises);

        $pAll->then(
            function () {
                $this->setReady(true);
            },
            function () {
                $this->setReady(false);
            }
        );
    }

    /**
     * @param $args
     * @throws \Exception
     */
    public function addStateHandler($args)
    {
        $uri        = isset($args[0]->uri) ? $args[0]->uri : null;
        $handlerUri = isset($args[0]->handler_uri) ? $args[0]->handler_uri : null;
        $options    = isset($args[0]->options) && is_object($args[0]->options) ? $args[0]->options : new \stdClass();

        $matchType = SubscribeMessage::getMatchTypeFromOption($options);
        $matcher   = $this->broker->getMatcherForMatchType($matchType);

        if ($uri === null) {
            throw new \Exception("No uri set for state handler registration.");
        }

        if ($handlerUri === null) {
            throw new \Exception("No handler uri set for state handler registration.");
        }

        if ($matcher === false) {
            throw new \Exception("State handler match type \"" . $matchType . "\" is not registered.");
        }

        $stateHandlerRegistration = new StateHandlerRegistration($this->getSession(), $handlerUri, $uri, $options, $matcher);

        $this->stateHandlerRegistrations[] = $stateHandlerRegistration;

        $this->mapNewStateHandlerRegistration($stateHandlerRegistration);
    }

    /**
     * @param $args
     */
    public function removeStateHandler($args)
    {
    }

    /**
     * @param StateHandlerRegistration $stateHandlerRegistration
     */
    private function mapNewStateHandlerRegistration($stateHandlerRegistration)
    {
        $subscriptionGroups = $this->broker->getSubscriptionGroups();
        /** @var SubscriptionGroup $subscriptionGroup */
        foreach ($subscriptionGroups as $subscriptionGroup) {
            // only check groups without an existing state handler
            if (!$this->stateHandlerMap->contains($subscriptionGroup)
                || ($this->stateHandlerMap->contains($subscriptionGroup) && $this->stateHandlerMap[$subscriptionGroup] === null)
            ) {
                if ($stateHandlerRegistration->handlesStateFor($subscriptionGroup)) {
                    $this->stateHandlerMap[$subscriptionGroup] = $stateHandlerRegistration;
                }
            }
        }
    }

    /**
     * Set ready flag
     *
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }

    /**
     * Get ready flag
     *
     * @return boolean
     */
    public function getReady()
    {
        return $this->ready;
    }

    /**
     * Called when we need to setup a registration
     * If there is a registration that works - then we set the handler
     * Otherwise, we set it to null
     *
     * @param SubscriptionGroup $subscriptionGroup
     */
    private function setupStateHandlerRegistration(SubscriptionGroup $subscriptionGroup)
    {
        /** @var StateHandlerRegistration $stateHandlerRegistration */
        foreach ($this->stateHandlerRegistrations as $stateHandlerRegistration) {
            if ($stateHandlerRegistration->handlesStateFor($subscriptionGroup)) {
                $this->stateHandlerMap->attach($subscriptionGroup, $stateHandlerRegistration);
                return;
            }
        }
        $this->stateHandlerMap->attach($subscriptionGroup, null);
    }

    /**
     * @param Subscription $subscription
     * @return StateHandlerRegistration|bool|null
     */
    private function getStateHandlerRegistrationForSubscription(Subscription $subscription)
    {
        $subscriptionGroup = $subscription->getSubscriptionGroup();
        if ($subscriptionGroup instanceof SubscriptionGroup) {
            if (!$this->stateHandlerMap->contains($subscriptionGroup)) {
                $this->setupStateHandlerRegistration($subscriptionGroup);
            }

            return $this->stateHandlerMap[$subscriptionGroup];
        }

        Logger::alert($this, "processSubscriptionAdded called with subscription that does not have subscriptionGroup set.");

        return false;
    }

    /**
     * @param Subscription $subscription
     */
    public function processSubscriptionAdded(Subscription $subscription)
    {
        $stateHandlerRegistration = $this->getStateHandlerRegistrationForSubscription($subscription);
        if ($stateHandlerRegistration !== false && $stateHandlerRegistration !== null) {
            $stateHandlerRegistration->publishState($subscription);
        }
    }

    /**
     * @param Subscription $subscription
     */
    public function processSubscriptionRemoved(Subscription $subscription)
    {

    }
}
