<?php

namespace Thruway\Role;

use Thruway\AbstractSession;
use Thruway\Logging\Logger;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\ErrorMessage;
use Thruway\Message\Message;
use Thruway\Message\PublishedMessage;
use Thruway\Message\PublishMessage;
use Thruway\Message\SubscribedMessage;
use Thruway\Message\SubscribeMessage;
use Thruway\Message\UnsubscribedMessage;
use Thruway\Message\UnsubscribeMessage;
use Thruway\Session;
use Thruway\Subscription;
use Thruway\Topic\Topic;
use Thruway\Topic\TopicManager;
use Thruway\Topic\TopicStateManagerDummy;
use Thruway\Topic\TopicStateManagerInterface;
use Thruway\Topic\TopicStateManager;

/**
 * Class Broker
 *
 * @package Thruway\Role
 */
class Broker extends AbstractRole
{

    /**
     * @var array
     */
    private $subscriptions;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    protected $manager;

    /**
     * @var TopicManager
     */
    private $topicManager;

    /**
     * @var TopicStateManagerInterface
     */
    private $topicStateManager;

    /**
     * Constructor
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function __construct(ManagerInterface $manager = null)
    {

        $this->subscriptions = [];

        $manager = $manager ? $manager : new ManagerDummy();

        $this->setTopicManager(new TopicManager());
        $this->setTopicStateManager(new TopicStateManagerDummy());
        $this->setManager($manager);
        Logger::debug($this, "Broker constructor");
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
     * Process publish message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\PublishMessage $msg
     */
    protected function processPublish(Session $session, PublishMessage $msg)
    {
        Logger::debug($this, "Processing publish message");

        //Check to make sure that the URI is valid
        if (!static::uriIsValid($msg->getTopicName())) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.invalid_uri'));

            return;
        }

        // see if they wanted confirmation
        if ($msg->acknowledge()) {
            $publicationId = Session::getUniqueId();
            $session->sendMessage(new PublishedMessage($msg->getRequestId(), $publicationId));
        }

        $topicManager = $this->getTopicManager();
        $topic        = $topicManager->getTopic($msg->getTopicName(), true);

        $topic->processPublish($session, $msg);

    }

    /**
     * Process subscribe message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\SubscribeMessage $msg
     */
    protected function processSubscribe(Session $session, SubscribeMessage $msg)
    {

        if (!static::uriIsValid($msg->getTopicName())) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.invalid_uri'));

            return;
        }

        $topicManager = $this->getTopicManager();
        $topic        = $topicManager->getTopic($msg->getTopicName(), true);
        $subscription = Subscription::createSubscriptionFromSubscribeMessage($session, $msg);

        $this->subscriptions[$subscription->getId()] = $subscription;

        $topic->addSubscription($subscription);
        $subscribedMsg = new SubscribedMessage($msg->getRequestId(), $subscription->getId());

        $session->sendMessage($subscribedMsg);

        if ($topic->hasStateHandler()) {
            $topicStateManager = $this->getTopicStateManager();
            $topicStateManager->publishState($subscription);
        }

    }

    /**
     * Process Unsubcribe message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\UnsubscribeMessage $msg
     */
    protected function processUnsubscribe(Session $session, UnsubscribeMessage $msg)
    {

        $subscription = $this->getSubscriptionById($msg->getSubscriptionId());

        if (!$subscription) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.no_such_subscription'));

            return;
        }

        if ($subscription) {
            $this->removeSubscription($subscription);
        }

        $session->sendMessage(new UnsubscribedMessage($msg->getRequestId()));
    }

    /**
     * Get subscription by ID
     *
     * @param $subscriptionId
     * @return \Thruway\Subscription|boolean
     */
    public function getSubscriptionById($subscriptionId)
    {
        /* @var $subscription \Thruway\Subscription */
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getId() == $subscriptionId) {
                return $subscription;
            }
        }

        return false;
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
     * Process a session leave
     *
     * @todo make this better
     * @param \Thruway\Session $session
     */
    public function leave(Session $session)
    {

        /* @var $subscription \Thruway\Subscription */
        foreach ($this->subscriptions as $subscription) {

            if ($subscription->getSession() === $session) {
                Logger::debug($this, "Leaving and unsubscribing: {$subscription->getTopic()}");

                $this->removeSubscription($subscription);
            }
        }
    }

    /**
     * @param Subscription $subscription
     */
    public function removeSubscription(Subscription $subscription)
    {
        $topicName = $subscription->getTopic();
        $topic     = $this->getTopicManager()->getTopic($topicName);

        $topic->removeSubscription($subscription->getId());
        unset ($this->subscriptions[$subscription->getId()]);
    }

    /**
     * Set manager
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
    }

    /**
     * get manager
     *
     * @return \Thruway\Manager\ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return array
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * @return TopicManager
     */
    public function getTopicManager()
    {
        return $this->topicManager;
    }

    /**
     * @param TopicManager $topicManager
     */
    public function setTopicManager($topicManager)
    {
        $this->topicManager = $topicManager;
    }

    /**
     * @return TopicStateManagerInterface
     */
    public function getTopicStateManager()
    {
        return $this->topicStateManager;
    }

    /**
     * @param TopicStateManagerInterface $topicStateManager
     */
    public function setTopicStateManager(TopicStateManagerInterface $topicStateManager)
    {
        $this->topicStateManager = $topicStateManager;
        $this->topicStateManager->setTopicManager($this->getTopicManager());
    }

    /**
     * Get list subscriptions
     *
     * @return array
     */
    public function managerGetSubscriptions()
    {
        $theSubscriptions = [];

        /** @var $subscription Subscription */
        foreach ($this->subscriptions as $subscription) {
            $theSubscriptions[] = [
                "id"      => $subscription->getId(),
                "topic"   => $subscription->getTopic(),
                "session" => $subscription->getSession()->getSessionId()
            ];
        }

        return [$theSubscriptions];
    }

}

