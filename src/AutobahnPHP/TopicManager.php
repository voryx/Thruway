<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/9/14
 * Time: 11:04 PM
 */

namespace AutobahnPHP;


class TopicManager {
    private $topics;

    function __construct()
    {
        $this->topics = array();
    }

    /**
     * @param string
     * @throws \UnexpectedValueException
     * @return Topic
     */
    public function getTopic($topicName) {
        // check to see if this is a valid name
        if (strlen($topicName) < 1) throw new \UnexpectedValueException("Topic name too short: " . $topicName);

        if ( ! array_key_exists($topicName, $this->topics)) {
            $this->topics[$topicName] = new Topic($topicName);
        }

        return $this->topics[$topicName];
    }
} 