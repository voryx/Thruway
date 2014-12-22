<?php


namespace Thruway\Topic;


/**
 * Class TopicManager
 * @package Thruway\Topic
 */
use Thruway\Common\Utils;


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
        if (!Utils::uriIsValid($topicName)) {
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
    public function addTopic(TopicInterface $topic)
    {
        $this->topics[$topic->getUri()] = $topic;
    }

    /**
     * @param $topic
     */
    public function removeTopic($topic)
    {
        $topicName = $topic instanceof TopicInterface ? $topic->getUri() : $topic;

        unset($this->topics[$topicName]);
    }

}

