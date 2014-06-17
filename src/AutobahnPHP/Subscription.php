<?php

namespace AutobahnPHP;


use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;

class Subscription
{

    /**
     * @var
     */
    private $id;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Topic
     */
    private $topic;

    /**
     * @var \stdClass
     */
    private $options;


    function __construct($topic, Session $session, $options = null)
    {

        $this->topic = $topic;
        $this->session = $session;
        $this->options = new \stdClass();
        $this->id = Session::getUniqueId();

    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \stdClass
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param \stdClass $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @param Topic $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }


} 