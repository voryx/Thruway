<?php

namespace Thruway\Role;

use Thruway\AbstractSession;
use Thruway\Common\Utils;
use Thruway\Event\LeaveRealmEvent;
use Thruway\Event\MessageEvent;
use Thruway\Logging\Logger;
use Thruway\Manager\ManageableInterface;
use Thruway\Manager\ManageableTrait;
use Thruway\Message\ErrorMessage;
use Thruway\Message\Message;
use Thruway\Message\PublishedMessage;
use Thruway\Message\PublishMessage;
use Thruway\Message\SubscribeMessage;
use Thruway\Message\UnsubscribeMessage;
use Thruway\Module\RealmModuleInterface;
use Thruway\Session;
use Thruway\Subscription\ExactMatcher;
use Thruway\Subscription\MatcherInterface;
use Thruway\Subscription\PrefixMatcher;
use Thruway\Subscription\StateHandlerRegistry;
use Thruway\Subscription\Subscription;
use Thruway\Subscription\SubscriptionGroup;

/**
 * Class Broker
 * @package Thruway\Role
 */
class Broker implements ManageableInterface, RealmModuleInterface
{
    use ManageableTrait;

    /**
     * @var array
     */
    protected $subscriptionGroups = [];

    /**
     * @var array
     */
    protected $matchers = [];

    /**
     * @var StateHandlerRegistry
     */
    protected $stateHandlerRegistry;

    /**
     *
     */
    function __construct()
    {
        $this->addMatcher(new ExactMatcher());
        $this->addMatcher(new PrefixMatcher());
    }


    /**
     * Handle received message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @throws \Exception
     * @return mixed|void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        Logger::debug($this,
            "Broker onMessage for " . json_encode($session->getTransport()->getTransportDetails()) . ": " . json_encode($msg)
        );

        if ($msg instanceof PublishMessage):
            $this->processPublish($session, $msg);
        elseif ($msg instanceof SubscribeMessage):
            $this->processSubscribe($session, $msg);
        elseif ($msg instanceof UnsubscribeMessage):
            $this->processUnsubscribe($session, $msg);
        else:
            throw new \Exception("Unhandled message type sent to broker: " . get_class($msg));
        endif;
    }

    /**
     * Handle message
     * Returns true if this role handles this message.
     *
     * @param \Thruway\Message\Message $msg
     * @return boolean
     */
    public function handlesMessage(Message $msg)
    {
        $handledMsgCodes = [
            Message::MSG_SUBSCRIBE,
            Message::MSG_UNSUBSCRIBE,
            Message::MSG_PUBLISH
        ];

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * Return supported features
     *
     * @return \stdClass
     */
    public function getFeatures()
    {
        $features = new \stdClass();

        $features->subscriber_blackwhite_listing = true;
        $features->publisher_exclusion           = true;
        $features->subscriber_metaevents         = true;

        return $features;
    }

    /**
     * Process subscribe message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\SubscribeMessage $msg
     * @throws \Exception
     */
    protected function processSubscribe(Session $session, SubscribeMessage $msg)
    {
        // get a subscription group "hash"
        /** @var MatcherInterface $matcher */
        $matcher = $this->getMatcherForMatchType($msg->getMatchType());
        if ($matcher === null) {
            Logger::alert($this,
                "no matching match type for \"" . $msg->getMatchType() . "\" for URI \"" . $msg->getUri() . "\"");
            return;
        }

        if (!$matcher->uriIsValid($msg->getUri(), $msg->getOptions())) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.invalid_uri'));

            return;
        }

        $matchHash = $matcher->getMatchHash($msg->getUri(), $msg->getOptions());

        if (!isset($this->subscriptionGroups[$matchHash])) {
            $this->subscriptionGroups[$matchHash] = new SubscriptionGroup($matcher, $msg->getUri(), $msg->getOptions());
        }

        /** @var SubscriptionGroup $subscriptionGroup */
        $subscriptionGroup = $this->subscriptionGroups[$matchHash];
        $subscription      = $subscriptionGroup->processSubscribe($session, $msg);

        $registry = $this->getStateHandlerRegistry();
        if ($registry !== null) {
            $registry->processSubscriptionAdded($subscription);
        }
    }

    /**
     * Process publish message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\PublishMessage $msg
     */
    protected function processPublish(Session $session, PublishMessage $msg)
    {
        if ($msg->getPublicationId() === null) {
            $msg->setPublicationId(Utils::getUniqueId());
        }

        /** @var SubscriptionGroup $subscriptionGroup */
        foreach ($this->subscriptionGroups as $subscriptionGroup) {
            $subscriptionGroup->processPublish($session, $msg);
        }

        if ($msg->acknowledge()) {
            $session->sendMessage(new PublishedMessage($msg->getRequestId(), $msg->getPublicationId()));
        }
    }

    /**
     * Process Unsubscribe message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\UnsubscribeMessage $msg
     */
    protected function processUnsubscribe(Session $session, UnsubscribeMessage $msg)
    {
        $subscription = false;
        // should probably be more efficient about this - maybe later
        /** @var SubscriptionGroup $subscriptionGroup */
        foreach ($this->subscriptionGroups as $subscriptionGroup) {
            $subscription = $subscriptionGroup->processUnsubscribe($session, $msg);
        }

        if ($subscription === false) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.no_such_subscription'));

            return;
        }
    }

    /**
     * @param MatcherInterface $matcher
     * @return bool
     */
    public function addMatcher(MatcherInterface $matcher)
    {
        foreach ($matcher->getMatchTypes() as $matchType) {
            if (isset($this->matchers[$matchType])) {
                return false;
            }
        }

        foreach ($matcher->getMatchTypes() as $matchType) {
            $this->matchers[$matchType] = $matcher;
        }

        return true;
    }

    /**
     * @param $matchType
     * @return MatcherInterface|bool
     */
    public function getMatcherForMatchType($matchType)
    {
        if (isset($this->matchers[$matchType])) {
            return $this->matchers[$matchType];
        }

        return false;
    }

    /**
     * @param Session $session
     */
    public function leave(Session $session)
    {
        /** @var SubscriptionGroup $subscriptionGroup */
        foreach ($this->subscriptionGroups as $subscriptionGroup) {
            /** @var Subscription $subscription */
            foreach ($subscriptionGroup->getSubscriptions() as $subscription) {
                if ($subscription->getSession() === $session) {
                    $subscriptionGroup->removeSubscription($subscription);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function managerGetSubscriptions()
    {
        return [$this->getSubscriptions()];
    }

    /**
     * @return array
     */
    public function getSubscriptions()
    {
        // collect all the subscriptions into an array
        $subscriptions = [];
        /** @var SubscriptionGroup $subscriptionGroup */
        foreach ($this->subscriptionGroups as $subscriptionGroup) {
            $subscriptions = array_merge($subscriptions, $subscriptionGroup->getSubscriptions());
        }

        return $subscriptions;
    }

    /**
     * @param $id
     * @return bool
     */
    public function getSubscriptionById($id)
    {
        /** @var SubscriptionGroup $subscriptionGroup */
        foreach ($this->subscriptionGroups as $subscriptionGroup) {
            if ($subscriptionGroup->containsSubscriptionId($id)) {
                return $subscriptionGroup->getSubscriptions()[$id];
            }
        }

        return false;
    }

    /**
     * @return StateHandlerRegistry
     */
    public function getStateHandlerRegistry()
    {
        return $this->stateHandlerRegistry;
    }

    /**
     * @param StateHandlerRegistry $stateHandlerRegistry
     */
    public function setStateHandlerRegistry($stateHandlerRegistry)
    {
        $this->stateHandlerRegistry = $stateHandlerRegistry;
    }

    /**
     * @return array
     */
    public function getSubscriptionGroups()
    {
        return $this->subscriptionGroups;
    }

    public function handlePublishMessage(MessageEvent $event) {
        $this->processPublish($event->session, $event->message);
    }

    public function handleSubscribeMessage(MessageEvent $event) {
        $this->processSubscribe($event->session, $event->message);
    }

    public function handleUnsubscribeMessage(MessageEvent $event) {
        $this->processUnsubscribe($event->session, $event->message);
    }

//    public function handleErrorMessage(MessageEvent $event) {
//        if ($this->handlesMessage($event->message)) {
//
//        }
//    }

    public function handleLeaveRealm(LeaveRealmEvent $event) {
        $this->leave($event->session);
    }

    /** @return array */
    public function getSubscribedRealmEvents()
    {
        return [
            "PublishMessageEvent" => ["handlePublishMessage", 10],
            "SubscribeMessageEvent" => ["handleSubscribeMessage", 10],
            "UnsubscribeMessageEvent" => ["handleUnsubscribeMessage", 10],
            //"ErrorMessageEvent" => ["handleErrorMessage", 10],
            "LeaveRealm" => ["handleLeaveRealm", 10],
        ];
    }


}