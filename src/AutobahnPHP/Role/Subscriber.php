<?php

namespace AutobahnPHP\Role;


use AutobahnPHP\AbstractSession;
use AutobahnPHP\ClientSession;
use AutobahnPHP\Message\ErrorMessage;
use AutobahnPHP\Message\EventMessage;
use AutobahnPHP\Message\Message;
use AutobahnPHP\Message\SubscribedMessage;
use AutobahnPHP\Message\SubscribeMessage;
use AutobahnPHP\Message\UnsubscribedMessage;
use AutobahnPHP\Session;

/**
 * Class Subscriber
 * @package AutobahnPHP\Role
 */
class Subscriber extends AbstractRole
{

    /**
     * @var \AutobahnPHP\ClientSession
     */
    private $session;

    /**
     * @var array
     */
    private $subscriptions;

    /**
     * @param ClientSession $session
     */
    function __construct(ClientSession $session)
    {
        $this->session = $session;

        $this->subscriptions = array();
    }

    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        switch ($msg) {
            case ($msg instanceof SubscribedMessage):
                $this->processSubscribed($session, $msg);
                break;
            case ($msg instanceof UnsubscribedMessage):
                $this->processUnsubscribed($session, $msg);
                break;
            case ($msg instanceof EventMessage):
                $this->processEvent($session, $msg);
                break;
            default:
                $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        }
    }

    /**
     * @param ClientSession $session
     * @param SubscribedMessage $msg
     */
    public function processSubscribed(ClientSession $session, SubscribedMessage $msg)
    {
        foreach ($this->subscriptions as $key => $subscription) {
            if ($subscription["request_id"] === $msg->getRequestId()) {
                $this->subscriptions[$key]['subscription_id'] = $msg->getSubscriptionId();
                break;
            }
        }
    }

    /**
     * @param ClientSession $session
     * @param UnsubscribedMessage $msg
     */
    public function processUnsubscribed(ClientSession $session, UnsubscribedMessage $msg)
    {

    }

    /**
     * @param ClientSession $session
     * @param EventMessage $msg
     */
    public function processEvent(ClientSession $session, EventMessage $msg)
    {
        foreach ($this->subscriptions as $key => $subscription) {
            if ($subscription["subscription_id"] === $msg->getSubscriptionId()) {
                $subscription["callback"]($msg->getArgs());
                break;
            }
        }
    }


    /**
     * @param Message $msg
     * @return mixed
     */
    public function handlesMessage(Message $msg)
    {
        $handledMessages = array(
            Message::MSG_SUBSCRIBED,
            Message::MSG_UNSUBSCRIBED,
            Message::MSG_EVENT
        );

        return in_array($msg->getMsgCode(), $handledMessages);
    }

    /**
     * @param $topicName
     * @param $callback
     */
    public function subscribe($topicName, $callback)
    {
        $requestId = Session::getUniqueId();

        $subscription = ["topic_name" => $topicName, "callback" => $callback, "request_id" => $requestId];

        array_push($this->subscriptions, $subscription);


        $options = new \stdClass();
        $subscribeMsg = new SubscribeMessage($requestId, $options, $topicName);
        $this->session->sendMessage($subscribeMsg);
    }


} 