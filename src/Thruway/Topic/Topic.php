<?php


namespace Thruway\Topic;

use Thruway\Common\Utils;
use Thruway\Logging\Logger;
use Thruway\Message\EventMessage;
use Thruway\Message\PublishMessage;
use Thruway\Session;
use Thruway\Subscription;

/**
 * Class Topic
 * @package Thruway\Topic
 */
class Topic
{

    /**
     * @var
     */
    private $uri;

    /**
     * @var array
     */
    private $subscriptions;

    /**
     * @var string
     */
    private $stateHandler;

    /**
     * @param string $uri
     */
    function __construct($uri)
    {
        $this->uri           = $uri;
        $this->subscriptions = [];
    }

    /**
     * @return string
     */
    function __toString()
    {
        return $this->uri;
    }


    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param Subscription $subscription
     */
    public function addSubscription(Subscription $subscription)
    {
        $this->subscriptions[$subscription->getId()] = $subscription;
    }

    /**
     * @param $subscriptionId
     */
    public function removeSubscription($subscriptionId)
    {
        unset($this->subscriptions[$subscriptionId]);
    }

    /**
     * @return array
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * @param Session $session
     * @param PublishMessage $msg
     */
    public function processPublish(Session $session, PublishMessage $msg)
    {
        foreach ($this->getSubscriptions() as $subscription) {
            $this->sendEventMessage($session, $msg, $subscription);
        }
    }

    /**
     * Get state handler
     * 
     * @return string
     */
    public function getStateHandler()
    {
        return $this->stateHandler;
    }

    /**
     * Check has handler
     * 
     * @return boolean
     */
    public function hasStateHandler() 
    {
        if ($this->stateHandler !== null) {
            return true;
        }

        return false;
    }

    /**
     * Set state handler
     * 
     * @param string $handlerUri
     * @throws \Exception
     */
    public function setStateHandler($handlerUri)
    {
        if (!Utils::uriIsValid($handlerUri)) {
            Logger::error($this, "Invalid URI");
            throw new \InvalidArgumentException("Invalid URI");
        }

        $this->stateHandler = $handlerUri;
    }

    /**
     *  Remove the handler URI
     */
    public function removeStateHandler()
    {
        $this->stateHandler = null;
    }

    /**
     * Send an Event Message for each subscription
     * 
     * @param \Thruway\Session $session
     * @param \Thruway\Message\PublishMessage $msg
     * @param \Thruway\Message\Subscription $subscription
     */
    private function sendEventMessage(Session $session, PublishMessage $msg, Subscription $subscription)
    {
        $sessionId = $subscription->getSession()->getSessionId();

        if ($msg->getTopicName() == $subscription->getTopic()
            && (!$msg->excludeMe() || $subscription->getSession() != $session)
            && !$msg->isExcluded($sessionId)
            && $msg->isWhiteListed($sessionId)
        ) {
            $eventMsg = EventMessage::createFromPublishMessage($msg, $subscription->getId());
            if ($subscription->isDisclosePublisher() === true) {
                $eventMsg->disclosePublisher($session);
            }
            $subscription->getSession()->sendMessage($eventMsg);
        }

    }

}
