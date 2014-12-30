<?php

namespace Thruway\Manager;

interface ManageableInterface {
    public function setManager(ManagerInterface $manager);
    public function getManager();
} 