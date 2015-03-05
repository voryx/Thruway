<?php


namespace Thruway\Manager;


/**
 * Class ManageableTrait
 * @package Thruway\Manager
 */
trait ManageableTrait
{
    /**
     * @var ManagerInterface
     */
    protected $manager;

    /**
     * @return ManagerInterface
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param ManagerInterface $manager
     */
    public function setManager(ManagerInterface $manager)
    {
        $this->manager = $manager;
    }
}