<?php

namespace Thruway\Role;


use Thruway\AbstractSession;
use Thruway\ClientSession;
use Thruway\Message\ErrorMessage;
use Thruway\Message\EventMessage;
use Thruway\Message\Message;
use Thruway\Message\SubscribedMessage;
use Thruway\Message\SubscribeMessage;
use Thruway\Message\UnsubscribedMessage;
use Thruway\Session;

/**
 * Class Subscriber
 * @package Thruway\Role
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
                call_user_func_array($subscription["callback"], [$msg->getArguments(), $msg->getArgumentsKw(), $msg->getDetails(), $msg->getPublicationId()]);
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