<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/7/14
 * Time: 12:02 PM
 */

namespace AutobahnPHP\Role;


use AutobahnPHP\AbstractSession;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\EventMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\PublishedMessage;
use AutobahnPHP\Message\PublishMessage;
use AutobahnPHP\Message\SubscribedMessage;
use AutobahnPHP\Message\SubscribeMessage;
use AutobahnPHP\Message\UnregisterMessage;
use AutobahnPHP\Message\UnsubscribedMessage;
use AutobahnPHP\Message\UnsubscribeMessage;
use AutobahnPHP\Session;
use AutobahnPHP\Subscription;

/**
 * Class Broker
 * @package AutobahnPHP\Role
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
     *
     */
    function __construct()
    {
        $this->subscriptions = new \SplObjectStorage();
        $this->topics = array();
    }

    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed|void
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {

        if ($msg instanceof PublishMessage):
            $this->processPublish($session, $msg);
        elseif ($msg instanceof SubscribeMessage):
            $this->processSubscribe($session, $msg);
        elseif ($msg instanceof UnsubscribedMessage):
            $this->processUnsubscribe($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
    }

    /**
     * @param Session $session
     * @param PublishMessage $msg
     */
    public function processPublish(Session $session, PublishMessage $msg)
    {
        echo "got publish\n";

        $receivers = isset($this->topics[$msg->getTopicName()]) ? $this->topics[$msg->getTopicName()] : null;

        //If the topic doesn't have any subscribers
        if (empty($receivers)) {
            echo "Invalid topic: {$msg->getTopicName()}";
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage(
            //not sure if this is the right error or if we need to send anything back here
                $errorMsg->setErrorURI('wamp.error.invalid_topic')
            );
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
     * @param Session $session
     * @param SubscribeMessage $msg
     */
    public function processSubscribe(Session $session, SubscribeMessage $msg)
    {

        if (!isset($this->topics[$msg->getTopicName()])) {
            $this->topics[$msg->getTopicName()] = array();
        }

        array_push($this->topics[$msg->getTopicName()], $session);

        //Check if this session has not already subscribed for this topic
        $subscriptionCheck = $this->checkSubscriptions($session->getSessionId(), $msg->getTopicName());

        if (!$subscriptionCheck) {
            $subscription = new Subscription($msg->getTopicName(), $session);
            $this->subscriptions->attach($subscription);
            $subscribedMsg = new SubscribedMessage($msg->getRequestId(), $msg->getTopicName());
            $session->sendMessage($subscribedMsg);
        }

    }

    /**
     * @param Session $session
     * @param UnsubscribeMessage $msg
     * @return UnsubscribedMessage
     */
    public function processUnsubscribe(Session $session, UnsubscribeMessage $msg)
    {

        $subscription = $this->getSubscriptionById($msg->getSubscriptionId());

        if (!$subscription || !isset($this->topics[$subscription->getTopic()])) {
            $errorMsg = ErrorMessage::createErrorMessageFromMessage($msg);
            $session->sendMessage($errorMsg->setErrorURI('wamp.error.no_such_subscription'));
        }

        $topicName = $subscription->getTopic();
        $subscribers = $this->topics[$topicName];

        /* @var $subscriber Session */
        foreach ($this->topics[$topicName] as $key => $subscriber) {
            if ($subscriber == $session) {
                unset($subscribers[$key]);
            }
        }

        $this->subscriptions->detach($subscription);

        $session->sendMessage(new UnsubscribedMessage($msg->getRequestId()));
    }

    /**
     * @param $sessionId
     * @param $topicName
     * @return Subscription|bool
     */
    public function checkSubscriptions($sessionId, $topicName)
    {
        /* @var $subscription Subscription */
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getSession()->getSessionId() == $sessionId && $subscription->getTopic() == $topicName) {
                return $subscription;
            }
        }

        return false;
    }

    /**
     * @param $subscriptionId
     * @return Subscription|bool
     */
    public function getSubscriptionById($subscriptionId)
    {
        /* @var $subscription Subscription */
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getId() == $subscriptionId) {
                return $subscription;
            }
        }

        return false;
    }

    /**
     * @param Message $msg
     * @return bool
     */
    public function handlesMessage(Message $msg)
    {
        $handledMsgCodes = array(
            Message::MSG_SUBSCRIBE,
            Message::MSG_UNSUBSCRIBE,
            Message::MSG_PUBLISH
        );

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * //@todo make this better
     * @param Session $session
     */
    public function leave(Session $session)
    {
        $this->subscriptions->rewind();
        while ($this->subscriptions->valid()) {
            /* @var $subscription Subscription */
            $subscription = $this->subscriptions->current();
            $this->subscriptions->next();
            if ($subscription->getSession() == $session) {
                echo "Leaving and unsubscribing: {$subscription->getTopic()}\n";
                $this->subscriptions->detach($subscription);
            }
        }

        foreach ($this->topics as $topicName => $subscribers) {
            foreach ($subscribers as $key => $subscriber) {
                if ($session == $subscriber) {
                    unset($subscribers[$key]);
                    echo "Removing session from topic list: {$topicName}\n";

                }
            }
        }
    }
} 