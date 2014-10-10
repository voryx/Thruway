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
     */
    public function __construct(Session $session, $procedureName)
    {
        $this->id            = Session::getUniqueId();
        $this->session       = $session;
        $this->procedureName = $procedureName;
    }

    /**
     * Get registration ID
     * 
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get procedure name
     * 
     * @return string
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * Get seesion
     * 
     * @return \Thruway\Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Get disclose caller
     * 
     * @return mixed
     */
    public function getDiscloseCaller()
    {
        return $this->discloseCaller;
    }

    /**
     * Set disclose caller
     * 
     * @param mixed $discloseCaller
     */
    public function setDiscloseCaller($discloseCaller)
    {
        $this->discloseCaller = $discloseCaller;
    }

}