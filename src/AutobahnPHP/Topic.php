<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 11:04 PM
 */

namespace AutobahnPHP;


use AutobahnPHP\Message\EventMessage;

class Topic {

    private $topicName;

    /**
     * @var \SplObjectStorage
     */
    private $subscriptions;

    function __construct($topicName)
    {
        $this->subscriptions = new \SplObjectStorage();
        $this->topicName = $topicName;
    }

    /**
     * @return mixed
     */
    public function getTopicName()
    {
        return $this->topicName;
    }

    public function getSubscription(Session $session, $options = null) {
        // TODO should see if we have a subscription that matches
        if ( ! $this->subscriptions->contains($session)) {
            $this->subscriptions->attach($session);
        } else {
            echo "Already subscribed...\n";
        }

        return $session;
    }

    public function unsubscribe($session) {
        $this->subscriptions->detach($session);
    }

    public function publish(Session $session, EventMessage $msg) {
        echo "Publishing event: " . $msg->getSerializedMessage() . "\n";
        /* @var $subscription Session */
        foreach($this->subscriptions as $subscription) {
            echo ".\n";
            if ($session !== $subscription) {
                $subscription->sendMessage($msg);
            }
        }
    }
} 