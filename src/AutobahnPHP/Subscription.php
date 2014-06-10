<?php

namespace AutobahnPHP;


class Subscription {

    private $subscriptionId;

    /**
     * @var \SplObjectStorage
     */
    private $sessions;

    /**
     * @var Topic
     */
    private $topic;

    function __construct(Topic $topic)
    {
        $this->sessions = new \SplObjectStorage();
        $this->topic = $topic;
    }


} 