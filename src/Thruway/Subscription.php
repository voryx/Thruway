<?php

namespace Thruway;

use Thruway\Message\SubscribeMessage;
use Thruway\Message\Traits\OptionsTrait;

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
    private $topic;


    /*
     * @var boolean
     */
    /**
     * @var bool
     */
    private $disclosePublisher;

    /**
     * Constructor
     *
     * @param string $topic
     * @param \Thruway\Session $session
     * @param mixed $options
     */
    public function __construct($topic, Session $session, $options = null)
    {

        $this->topic             = $topic;
        $this->session           = $session;
        $this->id                = Session::getUniqueId();
        $this->disclosePublisher = false;

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
     * Set topic name
     *
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    /**
     * Get topic name
     *
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
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
        //@todo implement pauseForState
    }

    /**
     * @param $lastPublicationId
     */
    public function unPauseForState($lastPublicationId = null)
    {
        //@todo implement unPauseForState
    }
} 