<?php

namespace Thruway\Topic;

use Thruway\Subscription;


/**
 * Class TopicStateManagerDummy
 * @package Thruway
 */
class TopicStateManagerDummy implements TopicStateManagerInterface
{

    /**
     * {@inheritdoc}
     */
    public function publishState(Subscription $subscription)
    {
        //This is intentionally left empty
    }

    /**
     * {@inheritdoc}
     */
    public function setTopicManager(TopicManager $topicManager)
    {
        //Nothing to do here
    }
}