<?php


namespace Thruway\Topic;


/**
 * Class TopicManager
 * @package Thruway\Topic
 */
use Thruway\Role\AbstractRole;

/**
 * Class TopicManager
 * @package Thruway\Topic
 */
class TopicManager
{

    /**
     * @var array of Topics
     */
    private $topics = [];

    /**
     * @return array
     */
    public function getTopics()
    {
        return $this->topics;
    }

    /**
     * @param array $topics
     */
    public function setTopics($topics)
    {
        $this->topics = $topics;
    }

    /**
     * @param $topicName
     * @param bool $create
     * @return null|Topic
     * @throws \Exception
     */
    public function getTopic($topicName, $create = false)
    {
        if (!AbstractRole::uriIsValid($topicName)) {
            throw new \Exception("Invalid URI");
        }

        $topic = isset($this->topics[$topicName]) ? $this->topics[$topicName] : null;

        if (!$topic && $create === true) {
            $topic = new Topic($topicName);
            $this->addTopic($topic);
        }

        return $topic;
    }

    /**
     * @param Topic $topic
     */
    public function addTopic(Topic $topic)
    {
        $this->topics[$topic->getUri()] = $topic;
    }

    /**
     * @param $topic
     */
    public function removeTopic($topic)
    {
        $topicName = $topic instanceof Topic ? $topic->getUri() : $topic;

        unset($this->topics[$topicName]);
    }

}

