<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/12/14
 * Time: 1:03 PM
 */

namespace Thruway;


/**
 * Class Registration
 * @package Thruway
 */
class Registration
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
     * @var
     */
    private $procedureName;

    /**
     * @var
     */
    private $discloseCaller;


    /**
     * @param Session $session
     * @param $procedureName
     * @param $requestId
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
     * @return mixed
     */
    public function getProcedureName()
    {
        return $this->procedureName;
    }

    /**
     * @return Session
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