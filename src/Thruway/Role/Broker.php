<?php

namespace Thruway\Role;

use Thruway\AbstractSession;
use Thruway\Manager\ManagerDummy;
use Thruway\Manager\ManagerInterface;
use Thruway\Message\ErrorMessage;
use Thruway\Message\EventMessage;
use Thruway\Message\Message;
use Thruway\Message\PublishedMessage;
use Thruway\Message\PublishMessage;
use Thruway\Message\SubscribedMessage;
use Thruway\Message\SubscribeMessage;
use Thruway\Message\UnsubscribedMessage;
use Thruway\Message\UnsubscribeMessage;
use Thruway\Session;
use Thruway\Subscription;

/**
 * Class Broker
 *
 * @package Thruway\Role
 */
class Broker extends AbstractRole
{

    /**
     * @var \SplObjectStorage
     */
    private $subscriptions;

    /**
     * @var array
     */
    private $topics;

    /**
     * @var \Thruway\Manager\ManagerInterface
     */
    protected $manager;

    /**
     * Constructor
     *
     * @param \Thruway\Manager\ManagerInterface $manager
     */
    function __construct(ManagerInterface $manager = null)
    {

        $this->subscriptions = new \SplObjectStorage();
        $this->topics        = [];
        $manager             = $manager ? $manager : new ManagerDummy();

        $this->setManager($manager);
        $this->getManager()->debug("Broker constructor");
    }

    /**
     * Handle process revieced message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @return mixed|void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        $this->getManager()->debug(
            "Broker onMessage for " . json_encode($session->getTransport()->getTransportDetails()) . ": " . json_encode($msg)
        );

        if ($msg instanceof PublishMessage):
            $this->processPublish($session, $msg);
        elseif ($msg instanceof SubscribeMessage):
            $this->processSubscribe($session, $msg);
        elseif ($msg instanceof UnsubscribeMessage):
            $this->processUnsubscribe($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
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
        $this->getManager()->debug("processing publish message");

        $receivers = isset($this->topics[$msg->getTopicName()]) ? $this->topics[$msg->getTopicName()] : null;

        //If the topic doesn't have any subscribers
        if (empty($receivers)) {
            $receivers = [];
        }

        // see if they wanted confirmation
        $options = $msg->getOptions();
        if (is_array($options)) {
            if (isset($options['acknowledge']) && $options['acknowledge'] == true) {
                $publicationId = Session::getUniqueId();
                $session->sendMessage(
                    new PublishedMessage($msg->getRequestId(), $publicationId)
                );
            }
        }

        $eventMsg = EventMessage::createFromPublishMessage($msg);

        /* @var $receiver Session */
        foreach ($receivers as $receiver) {
            if ($receiver != $session) {
                $receiver->sendMessage($eventMsg);
            }
        }
    }

    /**
     * Process subscribe message
     *
     * @param \Thruway\Session $session
     * @param \Thruway\Message\SubscribeMessage $msg
     */
    protected function processSubscribe(Session $session, SubscribeMessage $msg)
    {

        if (!$this->uriIsValid($msg->getTopicName())) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.invalid_uri'));

            return;
        }

        if (!isset($this->topics[$msg->getTopicName()])) {
            $this->topics[$msg->getTopicName()] = [];
        }

        array_push($this->topics[$msg->getTopicName()], $session);

        $subscription = new Subscription($msg->getTopicName(), $session);
        $this->subscriptions->attach($subscription);
        $subscribedMsg = new SubscribedMessage($msg->getRequestId(), $msg->getTopicName());
        $session->sendMessage($subscribedMsg);

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

        if (!$subscription || !isset($this->topics[$subscription->getTopic()])) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.no_such_subscription'));
        }

        $topicName   = $subscription->getTopic();
        $subscribers = $this->topics[$topicName];

        /* @var $subscriber Session */
        foreach ($this->topics[$topicName] as $key => $subscriber) {
            if ($subscriber == $session) {
                unset($subscribers[$key]);
            }
        }

        if ($subscription) {
            $this->subscriptions->detach($subscription);
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
        $this->subscriptions->rewind();
        while ($this->subscriptions->valid()) {
            /* @var $subscription \Thruway\Subscription */
            $subscription = $this->subscriptions->current();
            $this->subscriptions->next();
            if ($subscription->getSession() == $session) {
                $this->getManager()->debug("Leaving and unsubscribing: {$subscription->getTopic()}");
                $this->subscriptions->detach($subscription);
            }
        }

        foreach ($this->topics as $topicName => $subscribers) {
            foreach ($subscribers as $key => $subscriber) {
                if ($session == $subscriber) {
                    unset($subscribers[$key]);
                    $this->getManager()->debug("Removing session from topic list: {$topicName}");

                }
            }
        }
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
