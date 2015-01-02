<?php

namespace Thruway\Manager;

/**
 * Interface ManageableInterface
 * @package Thruway\Manager
 */
interface ManageableInterface
{
    /**
     * @param ManagerInterface $manager
     * @return mixed
     */
    public function setManager(ManagerInterface $manager);

    /**
     * @return mixed
     */
    public function getManager();
} 