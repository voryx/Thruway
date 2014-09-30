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
 *
 * @package Thruway\Role
 */
class Subscriber extends AbstractRole
{

    /**
     * @var array
     */
    private $subscriptions;

    /**
     * Constructor
     */
    function __construct()
    {

        $this->subscriptions = [];
    }

    /**
     * Handle on recieved message
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\Message $msg
     * @return void
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
     * process subscribed
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\SubscribedMessage $msg
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
     * process unsubscribed
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\UnsubscribedMessage $msg
     */
    public function processUnsubscribed(ClientSession $session, UnsubscribedMessage $msg)
    {
        foreach ($this->subscriptions as $key => $subscription) {
            if (isset($subscription['unsubscribed_request_id']) && $subscription['unsubscribed_request_id'] == $msg->getRequestId()) {
                /* @var $deferred \React\Promise\Deferred */
                $deferred = $subscription['unsubscribed_deferred'];
                $deferred->resolve();

                unset($this->subscriptions[$key]);
                return;
            }
        }
        $this->logger->error("---Got an Unsubscribed Message, but couldn't find corresponding request.\n");
    }

    /**
     * Process event
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\EventMessage $msg
     */
    public function processEvent(ClientSession $session, EventMessage $msg)
    {
        foreach ($this->subscriptions as $key => $subscription) {
            if ($subscription["subscription_id"] === $msg->getSubscriptionId()) {
                call_user_func_array($subscription["callback"],
                    [$msg->getArguments(), $msg->getArgumentsKw(), $msg->getDetails(), $msg->getPublicationId()]);
                break;
            }
        }
    }


    /**
     * Returns true if this role handles this message.
     *
     * @param \Thruway\Message\Message $msg
     * @return boolean
     */
    public function handlesMessage(Message $msg)
    {
        $handledMsgCodes = [
            Message::MSG_SUBSCRIBED,
            Message::MSG_UNSUBSCRIBED,
            Message::MSG_EVENT
        ];

        if (in_array($msg->getMsgCode(), $handledMsgCodes)) {
            return true;
        } elseif ($msg instanceof ErrorMessage && $msg->getErrorMsgCode() == Message::MSG_SUBSCRIBE) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * process subscribe
     *
     * @param \Thruway\ClientSession $session
     * @param string $topicName
     * @param \Closure $callback
     */
    public function subscribe(ClientSession $session, $topicName, $callback)
    {
        $requestId    = Session::getUniqueId();
        $options      = new \stdClass();
        $subscription = [
            "topic_name" => $topicName,
            "callback"   => $callback,
            "request_id" => $requestId,
            "options"    => $options
        ];

        array_push($this->subscriptions, $subscription);

        $subscribeMsg = new SubscribeMessage($requestId, $options, $topicName);
        $session->sendMessage($subscribeMsg);
    }

} 