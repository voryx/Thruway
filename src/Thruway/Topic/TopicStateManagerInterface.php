<?php


namespace Thruway\Topic;


use Thruway\Subscription;

/**
 * Interface TopicStateManagerInterface
 * @package Thruway\Topic
 */
interface TopicStateManagerInterface
{

    /**
     * Gets and published the topics state to this subscription
     *
     * @param Subscription $subscription
     * @return mixed
     */
    public function publishState(Subscription $subscription);

    /**
     * @param TopicManager $topicManager
     * @return mixed
     */
    public function setTopicManager(TopicManager $topicManager);
}