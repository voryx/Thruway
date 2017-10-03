<?php

namespace Thruway\Subscription;

use Thruway\Common\Utils;
use Thruway\Message\EventMessage;
use Thruway\Message\SubscribeMessage;
use Thruway\Message\Traits\OptionsTrait;
use Thruway\Session;

/**
 * Class Subscription
 */
class Subscription
{
    use OptionsTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var \Thruway\Session
     */
    private $session;

    /**
     * @var string
     */
    private $uri;

    /**
     * @var boolean
     */
    private $pausedForState;

    /**
     * @var \SplQueue
     */
    private $pauseQueue;

    /**
     * @var boolean
     */
    private $disclosePublisher;

    /**
     * @var SubscriptionGroup
     */
    private $subscriptionGroup;

    /**
     * Constructor
     *
     * @param string $uri
     * @param \Thruway\Session $session
     * @param mixed $options
     */
    public function __construct($uri, Session $session, $options = null)
    {

        $this->uri               = $uri;
        $this->session           = $session;
        $this->id                = Utils::getUniqueId();
        $this->disclosePublisher = false;
        $this->pausedForState    = false;
        $this->pauseQueue        = new \SplQueue();

        $this->setOptions($options);

    }

    /**
     * Create Subscription from SubscribeMessage
     *
     * @param Session $session
     * @param SubscribeMessage $msg
     * @return Subscription
     */
    public static function createSubscriptionFromSubscribeMessage(Session $session, SubscribeMessage $msg)
    {
        $options      = $msg->getOptions();
        $subscription = new Subscription($msg->getTopicName(), $session, $options);

        if (isset($options->disclose_publisher) && $options->disclose_publisher === true) {
            $subscription->setDisclosePublisher(true);
        }

        return $subscription;
    }

    /**
     * Get subscription ID
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set URI
     *
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Get URI
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get session
     *
     * @return \Thruway\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set session
     *
     * @param \Thruway\Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return SubscriptionGroup
     */
    public function getSubscriptionGroup()
    {
        return $this->subscriptionGroup;
    }

    /**
     * @param SubscriptionGroup $subscriptionGroup
     */
    public function setSubscriptionGroup(SubscriptionGroup $subscriptionGroup)
    {
        $this->subscriptionGroup = $subscriptionGroup;
    }

    /**
     * @return boolean
     */
    public function isDisclosePublisher()
    {
        return $this->disclosePublisher;
    }

    /**
     * @param boolean $disclosePublisher
     */
    public function setDisclosePublisher($disclosePublisher)
    {
        $this->disclosePublisher = $disclosePublisher;
    }

    /**
     * Pauses all non-state building event messages
     */
    public function pauseForState()
    {
        if ($this->pausedForState) {
            throw new \Exception('Tried to paused already paused subscription');
        }
        $this->pausedForState = true;
    }

    /**
     * @return bool
     */
    public function isPausedForState()
    {
        return $this->pausedForState;
    }

    /**
     * @param $lastPublicationId
     * @throws \Exception
     */
    public function unPauseForState($lastPublicationId = null)
    {
        if (!$this->pausedForState) {
            throw new \Exception('Tried to unpaused subscription that was not paused');
        }

        $this->pausedForState = false;

        $this->processStateQueue($lastPublicationId);
    }

    /**
     * @param null $lastPublicationId
     */
    private function processStateQueue($lastPublicationId = null)
    {
        if ($lastPublicationId !== null) {
            // create an array of pub ids
            // if we can't find the lastPublicationId in the queue
            // then we are going to assume it was before our time
            $pubIds = [];

            /** @var EventMessage $msg */
            foreach ($this->pauseQueue as $msg) {
                $pubIds[] = $msg->getPublicationId();
            }

            if (!in_array($lastPublicationId, $pubIds, true)) {
                $lastPublicationId = null;
            }
        }

        while (!$this->pauseQueue->isEmpty()) {
            $msg = $this->pauseQueue->dequeue();
            if ($lastPublicationId === null) {
                $this->sendEventMessage($msg);
            }
            if ($lastPublicationId === $msg->getPublicationId()) {
                $lastPublicationId = null;
            }
        }
    }

    /**
     * @param EventMessage $msg
     */
    public function sendEventMessage(EventMessage $msg)
    {
        if ($this->pausedForState && !$msg->isRestoringState()) {
            $this->pauseQueue->enqueue($msg);
            return;
        }

        $this->getSession()->sendMessage($msg);
    }
}
