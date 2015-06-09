<?php

namespace Thruway\Message;

use Thruway\Message\Traits\ArgumentsTrait;
use Thruway\Message\Traits\DetailsTrait;
use Thruway\Session;

/**
 * Class EventMessage
 * Event dispatched by Broker to Subscribers for subscription the event was matching.
 * <code>[EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict]</code>
 * <code>[EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict, PUBLISH.Arguments|list]</code>
 * <code>[EVENT, SUBSCRIBED.Subscription|id, PUBLISHED.Publication|id, Details|dict, PUBLISH.Arguments|list, PUBLISH.ArgumentsKw|dict]</code>
 *
 * @package Thruway\Message
 */
class EventMessage extends Message
{

    use DetailsTrait;
    use ArgumentsTrait;

    /**
     * @var int
     */
    private $subscriptionId;
    /**
     * @var int
     */
    private $publicationId;

    /**
     * @var
     */
    private $topic;

    /**
     * Constructor
     *
     * @param int $subscriptionId
     * @param int $publicationId
     * @param \stdClass $details
     * @param mixed $arguments
     * @param mixed $argumentsKw
     * @param null $topic
     */
    public function __construct($subscriptionId, $publicationId, $details, $arguments = null, $argumentsKw = null, $topic = null)
    {
        $this->setArguments($arguments);
        $this->setArgumentsKw($argumentsKw);
        $this->setDetails($details);
        $this->setPublicationId($publicationId);
        $this->setSubscriptionId($subscriptionId);
        $this->topic = $topic;
    }

    /**
     * Get message code
     *
     * @return int
     */
    public function getMsgCode()
    {
        return static::MSG_EVENT;
    }

    /**
     * This is used by get message parts to get the parts of the message beyond
     * the message code
     *
     * @return array
     */
    public function getAdditionalMsgFields()
    {
        $a = [$this->getSubscriptionId(), $this->getPublicationId(), $this->getDetails()];

        return array_merge($a, $this->getArgumentsForSerialization());
    }

    /**
     * Create event message from publish message
     *
     * @param \Thruway\Message\PublishMessage $msg
     * @param int $subscriptionId
     * @return \Thruway\Message\EventMessage
     */
    public static function createFromPublishMessage(PublishMessage $msg, $subscriptionId)
    {
        return new static(
          $subscriptionId,
          $msg->getPublicationId(),
          new \stdClass(),
          $msg->getArguments(),
          $msg->getArgumentsKw(),
          $msg->getTopicName()
        );
    }

    /**
     * Set publication ID
     *
     * @param int $publicationId
     */
    public function setPublicationId($publicationId)
    {
        $this->publicationId = $publicationId;
    }

    /**
     * Get publication ID
     *
     * @return int
     */
    public function getPublicationId()
    {
        return $this->publicationId;
    }

    /**
     * Set subscription ID
     *
     * @param int $subscriptionId
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
    }

    /**
     * Get subscription ID
     *
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * @param Session $session
     */
    public function disclosePublisher(Session $session)
    {

        $details             = $this->getDetails();
        $details->publisher  = $session->getSessionId();
        $details->topic      = $this->topic;
        $authenticationDetails = $session->getAuthenticationDetails();
        $details->authid     = $authenticationDetails->getAuthId();
        $details->authrole   = $authenticationDetails->getAuthRole();
        $details->authroles  = $authenticationDetails->getAuthRoles();
        $details->authmethod = $authenticationDetails->getAuthMethod();

        if ($authenticationDetails->getAuthExtra() !== null) {
            $details->_thruway_authextra = $authenticationDetails->getAuthExtra();
        }

    }

    /**
     * @return boolean
     */
    public function isRestoringState()
    {
        $restoringState = isset($this->getDetails()->_thruway_restoring_state) ? $this->getDetails()->_thruway_restoring_state : false;

        return $restoringState;
    }

    /**
     * @param boolean $restoringState
     */
    public function setRestoringState($restoringState)
    {
        $details = $this->getDetails();
        if (is_object($details)) {
            $details->_thruway_restoring_state = true;
        }
    }
}