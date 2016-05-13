<?php

namespace Thruway\Role;


use React\Promise\Deferred;
use React\Promise\Promise;
use Thruway\AbstractSession;
use Thruway\ClientSession;
use Thruway\Common\Utils;
use Thruway\Logging\Logger;
use Thruway\Message\ErrorMessage;
use Thruway\Message\EventMessage;
use Thruway\Message\Message;
use Thruway\Message\SubscribedMessage;
use Thruway\Message\SubscribeMessage;
use Thruway\Message\UnsubscribedMessage;
use Thruway\Message\UnsubscribeMessage;

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
     * @var array
     */
    private $unsubscriptionsPromises;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->subscriptions = [];
        $this->unsubscriptionsPromises = [];
    }

    /**
     * Return supported features
     *
     * @return \stdClass
     */
    public function getFeatures() {
        $features = new \stdClass();

//        $features->subscriber_metaevents = true;

        return $features;
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
        elseif ($msg instanceof ErrorMessage):
            $this->processError($session, $msg);
        else:
            $session->sendMessage(ErrorMessage::createErrorMessageFromMessage($msg));
        endif;
    }

    /**
     * Process error
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    protected function processError(AbstractSession $session, ErrorMessage $msg)
    {
        switch ($msg->getErrorMsgCode()) {
            case Message::MSG_SUBSCRIBE:
                $this->processSubscribeError($session, $msg);
                break;
            case Message::MSG_UNSUBSCRIBE:
                $this->processUnsubscribeError($session, $msg);
                break;
            default:
                Logger::critical($this, "Unhandled error");
        }
    }

    /**
     * Process subscribe error
     *
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    protected function processSubscribeError(AbstractSession $session, ErrorMessage $msg)
    {
        foreach ($this->subscriptions as $key => $subscription) {
            if ($subscription["request_id"] === $msg->getErrorRequestId()) {
                // reject the promise
                $this->subscriptions[$key]['deferred']->reject($msg);

                unset($this->subscriptions[$key]);
                break;
            }
        }
    }

    /**
     * process subscribed
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\SubscribedMessage $msg
     */
    protected function processSubscribed(ClientSession $session, SubscribedMessage $msg)
    {
        foreach ($this->subscriptions as $key => $subscription) {
            if ($subscription["request_id"] === $msg->getRequestId()) {
                $this->subscriptions[$key]['subscription_id'] = $msg->getSubscriptionId();
                $this->subscriptions[$key]['deferred']->resolve($msg);
                break;
            }
        }
    }
    
    /**
     * Process unsubscribe error
     * 
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Message\ErrorMessage $msg
     */
    protected function processUnsubscribeError(AbstractSession $session, ErrorMessage $msg)
    {
        $hasBeenFound = false;
        
        foreach ($this->subscriptions as $key => $subscription) {
            if ($subscription["unsubscribed_request_id"] === $msg->getErrorRequestId()) {
                $hasBeenFound = true;
                
                // reject the promise
                $this->subscriptions[$key]['deferred']->reject($msg);
                break;
            }
        }
        
        if ($hasBeenFound === false) {
            foreach ($this->unsubscriptionsPromises as $key => $unsubscriptionPromise) {
                if ($unsubscriptionPromise["request_id"] === $msg->getErrorRequestId()) {
                    $unsubscriptionPromise["deferred"]->reject($msg);
                    unset($this->unsubscriptionPromises[$key]);
                    break;
                }
            }
        }
    }

    /**
     * process unsubscribed
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\UnsubscribedMessage $msg
     */
    protected function processUnsubscribed(ClientSession $session, UnsubscribedMessage $msg)
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
//        $this->logger->error("---Got an Unsubscribed Message, but couldn't find corresponding request.\n");
    }

    /**
     * Process event
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Message\EventMessage $msg
     */
    protected function processEvent(ClientSession $session, EventMessage $msg)
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
            Message::MSG_EVENT,
            Message::MSG_SUBSCRIBE, // for error handling
            Message::MSG_UNSUBSCRIBE // for error handling
        ];

        $codeToCheck = $msg->getMsgCode();

        if ($msg instanceof ErrorMessage) {
            $codeToCheck = $msg->getErrorMsgCode();
        }

        if (in_array($codeToCheck, $handledMsgCodes)) {
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
     * @param callable $callback
     * @param $options
     * @return Promise
     */
    public function subscribe(ClientSession $session, $topicName, callable $callback, $options = null)
    {
        $requestId = Utils::getUniqueId();
        $options   = $options ? (object)$options : (object)[];
        $deferred  = new Deferred();

        $subscription = [
            "topic_name" => $topicName,
            "callback"   => $callback,
            "request_id" => $requestId,
            "options"    => $options,
            "deferred"   => $deferred
        ];
        
        array_push($this->subscriptions, $subscription);
        
        $subscribeMsg = new SubscribeMessage($requestId, $options, $topicName);
        $session->sendMessage($subscribeMsg);

        return $deferred->promise();
    }
	
    /**
     * process unsubscribe
     * @param ClientSession $session
     * @param type $topicName
     * @return boolean
     */
    public function unsubscribe(ClientSession $session, $topicName)
    {
        $requestId = Utils::getUniqueId();
        $subscriptionId = false;
        $deferred  = new Deferred();
        
        foreach ($this->subscriptions as $i => $subscription) {
            if ($subscription["topic_name"] == $topicName) {
                $subscriptionId = $subscription["subscription_id"];
                
                $this->subscriptions[$i]["unsubscribed_request_id"] = $requestId;
                $this->subscriptions[$i]["unsubscribed_deferred"] = $deferred;
            }
        }
        
        // In case the client never subscribed to this topic before
        if ($subscriptionId === false) {
            $unsubscriptionPromise = [
                "request_id" => $requestId,
                "deferred" => $deferred
            ];
            
            array_push($this->unsubscriptionsPromises, $unsubscriptionPromise);
        }
        
        $unsubscribeMessage = new UnsubscribeMessage($requestId, $subscriptionId);
        $session->sendMessage($unsubscribeMessage);
        
        return $deferred->promise();
    }
} 
