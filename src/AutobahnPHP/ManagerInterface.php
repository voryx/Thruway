<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/20/14
 * Time: 4:59 PM
 */

namespace AutobahnPHP;


interface ManagerInterface {
    public function addCallable($name, $callback);
} 