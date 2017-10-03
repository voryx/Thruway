<?php

namespace Thruway\Subscription;

use Thruway\Common\Utils;
use Thruway\Logging\Logger;
use Thruway\Message\EventMessage;
use Thruway\Message\PublishMessage;
use Thruway\Message\SubscribedMessage;
use Thruway\Message\SubscribeMessage;
use Thruway\Message\Traits\OptionsMatchTypeTrait;
use Thruway\Message\Traits\OptionsTrait;
use Thruway\Message\UnsubscribedMessage;
use Thruway\Message\UnsubscribeMessage;
use Thruway\Session;

/**
 * Class SubscriptionGroup
 *
 * This groups subscriptions that have exactly the same matching
 * criteria.
 *
 * @package Thruway
 */
class SubscriptionGroup
{
    use OptionsTrait;

    use OptionsMatchTypeTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $subscriptions = [];

    /**
     * @var string
     */
    private $uri;

    /**
     * @var string
     */
    private $stateHandler;

    /**
     * @var MatcherInterface
     */
    protected $matcher;

    /**
     * @var int
     */
    protected $lastPublicationId;

    /**
     * @param MatcherInterface $matcher
     * @param $uri
     * @param $options
     */
    public function __construct(MatcherInterface $matcher, $uri, $options)
    {
        $this->setOptions($options);
        $this->setUri($uri);
        $this->setMatcher($matcher);
        $this->lastPublicationId = 0;
    }

    /**
     * @param Subscription $subscription
     */
    public function addSubscription(Subscription $subscription)
    {
        $this->subscriptions[$subscription->getId()] = $subscription;
    }

    /**
     * @param Subscription $subscription
     */
    public function removeSubscription(Subscription $subscription)
    {
        if (isset($this->subscriptions[$subscription->getId()])) {
            unset($this->subscriptions[$subscription->getId()]);
        }
    }

    /**
     * @param Session $session
     * @param PublishMessage $msg
     */
    public function processPublish(Session $session, PublishMessage $msg)
    {
        if ($this->getMatcher()->matches($msg->getTopicName(), $this->getUri(), $this->getOptions())) {
            $this->lastPublicationId = $msg->getPublicationId();
            foreach ($this->getSubscriptions() as $subscription) {
                $this->sendEventMessage($session, $msg, $subscription);
            }
        }
    }

    /**
     * Send an Event Message for each subscription
     * @param Session $session
     * @param PublishMessage $msg
     * @param Subscription $subscription
     */
    private function sendEventMessage(Session $session, PublishMessage $msg, Subscription $subscription)
    {
        $sessionId             = $subscription->getSession()->getSessionId();
        $authroles             = [];
        $authid                = '';
        $authenticationDetails = $subscription->getSession()->getAuthenticationDetails();
        if ($authenticationDetails) {
            $authroles = $authenticationDetails->getAuthRoles();
            $authid    = $authenticationDetails->getAuthId();
        }

        if ((!$msg->excludeMe() || $subscription->getSession() != $session)
            && !$msg->isExcluded($sessionId)
            && $msg->isWhiteListed($sessionId)
            && $msg->hasEligibleAuthrole($authroles)
            && $msg->hasEligibleAuthid($authid)
        ) {
            $eventMsg = EventMessage::createFromPublishMessage($msg, $subscription->getId());
            if ($subscription->isDisclosePublisher() === true) {
                $eventMsg->disclosePublisher($session);
            }
            if ($this->getMatchType() !== 'exact') {
                $eventMsg->getDetails()->topic = $msg->getUri();
            }
            $subscription->sendEventMessage($eventMsg);
        }
    }

    /**
     * @return array
     */
    public function getSubscriptions()
    {
        return $this->subscriptions;
    }

    /**
     * @param array $subscriptions
     */
    public function setSubscriptions($subscriptions)
    {
        $this->subscriptions = $subscriptions;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getMatchType()
    {
        $options = $this->getOptions();
        if (is_object($options) && isset($options->match) && is_scalar($options->match)) {
            return $options->match;
        }

        return 'exact';
    }

    /**
     * @return string
     */
    public function getStateHandler()
    {
        return $this->stateHandler;
    }

    /**
     * @return bool
     */
    public function hasStateHandler()
    {
        if ($this->stateHandler !== null) {
            return true;
        }

        return false;
    }

    /**
     * @param string $handlerUri
     * @throws \Exception
     */
    public function setStateHandler($handlerUri)
    {
        if (!Utils::uriIsValid($handlerUri)) {
            Logger::error($this, 'Invalid URI');
            throw new \InvalidArgumentException('Invalid URI');
        }

        $this->stateHandler = $handlerUri;
    }

    /**
     * @return MatcherInterface
     */
    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * @param MatcherInterface $matcher
     */
    public function setMatcher($matcher)
    {
        $this->matcher = $matcher;
    }

    /**
     *  Remove the handler URI
     */
    public function removeStateHandler()
    {
        $this->stateHandler = null;
    }

    /**
     * @param Session $session
     * @param SubscribeMessage $msg
     * @return Subscription
     */
    public function processSubscribe(Session $session, SubscribeMessage $msg)
    {
        $subscription = Subscription::createSubscriptionFromSubscribeMessage($session, $msg);

        $this->addSubscription($subscription);
        $subscription->setSubscriptionGroup($this);

        Logger::debug($this, 'Added subscription to \'' . $this->getMatchType() . '\':\'' . $this->getUri() . '\'');

        $session->sendMessage(new SubscribedMessage($msg->getRequestId(), $subscription->getId()));

        return $subscription;
    }

    /**
     * @param Session $session
     * @param UnsubscribeMessage $msg
     * @return bool|Subscription
     */
    public function processUnsubscribe(Session $session, UnsubscribeMessage $msg)
    {
        if ($this->containsSubscriptionId($msg->getSubscriptionId())) {
            /** @var Subscription $subscription */
            $subscription = $this->subscriptions[$msg->getSubscriptionId()];
            if ($session !== $subscription->getSession()) {
                Logger::alert($this, 'Unsubscribe request from non-owner: ' . json_encode($msg));
                return false;
            }

            $this->removeSubscription($subscription);

            $session->sendMessage(new UnsubscribedMessage($msg->getRequestId()));
            return $subscription;
        }
        return false;
    }

    /**
     * @param $id
     * @return bool
     */
    public function containsSubscriptionId($id)
    {
        return isset($this->subscriptions[$id]);
    }

    /**
     * @param Session $session
     */
    public function leave(Session $session)
    {
        /** @var Subscription $subscription */
        foreach ($this->subscriptions as $subscription) {
            if ($subscription->getSession() === $session &&
                $this->containsSubscriptionId($subscription->getId())
            ) {
                unset($this->subscriptions[$subscription->getId()]);
            }
        }
    }

    /**
     * @return int
     */
    public function getLastPublicationId()
    {
        return $this->lastPublicationId;
    }
}
