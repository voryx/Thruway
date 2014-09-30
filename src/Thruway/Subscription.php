<?php

namespace Thruway;

/**
 * Class Subscription
 */
class Subscription
{

    /**
     * @var int
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

    /**
     * @var \stdClass
     */
    private $options;

    /**
     * Constructor
     *
     * @param string $topic
     * @param \Thruway\Session $session
     * @param mixed $options
     */
    function __construct($topic, Session $session, $options = null)
    {

        $this->topic   = $topic;
        $this->session = $session;
        $this->options = new \stdClass();
        $this->id      = Session::getUniqueId();

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
     * @param string $topic
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
     * @return \Thruway\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param \Thruway\Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

} 