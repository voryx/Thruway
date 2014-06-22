<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/20/14
 * Time: 4:59 PM
 */

namespace Thruway;


interface ManagerInterface {
    public function addCallable($name, $callback);
    function logIt($logLevel, $msg);
    function logInfo($msg);
    function logError($msg);
    function logWarning($msg);
    function logDebug($msg);
} 