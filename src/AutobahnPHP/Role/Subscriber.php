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
     * @var array
     */
    private $subscriptions;


    function __construct()
    {

        $this->subscriptions = array();
    }

    /**
     * @param AbstractSession $session
     * @param Message $msg
     * @return mixed
     */
    public function onMessage(AbstractSession $session, Message $msg)
    {
        if ($msg instanceof SubscribedMessage):
            $this->processSubscribed($session, $msg);
        elseif ($msg instanceof UnsubscribedMessage):
            $this->processUnsubscribed($session, $msg);
        elseif ($msg instanceof EventMessage):
            $this->processEvent($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
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
        $handledMsgCodes = array(
            Message::MSG_SUBSCRIBED,
            Message::MSG_UNSUBSCRIBED,
            Message::MSG_EVENT
        );

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_SUBSCRIBE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $topicName
     * @param $callback
     */
    public function subscribe(ClientSession $session, $topicName, $callback)
    {
        $requestId = Session::getUniqueId();
        $options = new \stdClass();
        $subscription = [
            "topic_name" => $topicName,
            "callback" => $callback,
            "request_id" => $requestId,
            "options" => $options
        ];

        array_push($this->subscriptions, $subscription);

        $subscribeMsg = new SubscribeMessage($requestId, $options, $topicName);
        $session->sendMessage($subscribeMsg);
    }
} 