<?php

namespace Thruway;

/**
 * Class Subscription
 */
class Subscription
{

    /**
     * @var string
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
    public function __construct($topic, Session $session, $options = null)
    {

        $this->topic   = $topic;
        $this->session = $session;
        $this->options = new \stdClass();
        $this->id      = Session::getUniqueId();

    }

    /**
     * Get subscription ID
     * 
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get subscription options
     * 
     * @return \stdClass
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set subscription options
     * 
     * @param \stdClass $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Set topic name
     * 
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
    }

    /**
     * Get topic name
     * 
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Get session
     * 
     * @return \Thruway\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set session
     * 
     * @param \Thruway\Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

} 