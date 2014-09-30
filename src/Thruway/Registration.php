<?php

namespace Thruway;


/**
 * Class Registration
 * 
 * @package Thruway
 */
class Registration
{
    /**
     * @var mixed
     */
    private $id;

    /**
     * @var \Thruway\Session
     */
    private $session;

    /**
     * @var string
     */
    private $procedureName;

    /**
     * @var mixed
     */
    private $discloseCaller;


    /**
     * Constructor
     * 
     * @param \Thruway\Session $session
     * @param string $procedureName
     * @param mixed $requestId
     */
    function __construct(Session $session, $procedureName)
    {
        $this->id = Session::getUniqueId();
        $this->session = $session;
        $this->procedureName = $procedureName;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @return \Thruway\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return mixed
     */
    public function getDiscloseCaller()
    {
        return $this->discloseCaller;
    }

    /**
     * @param mixed $discloseCaller
     */
    public function setDiscloseCaller($discloseCaller)
    {
        $this->discloseCaller = $discloseCaller;
    }
}