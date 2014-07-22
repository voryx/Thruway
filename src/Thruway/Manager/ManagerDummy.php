<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 6/20/14
 * Time: 5:07 PM
 */

namespace Thruway\Manager;

class ManagerDummy implements ManagerInterface {
    /**
     * This intentionally does nothing
     *
     * @param $name
     * @param $callback
     */
    public function addCallable($name, $callback)
    {

    }

    function logIt($logLevel, $msg)
    {
        echo $logLevel . ": " . $msg . "\n";
    }

    function logInfo($msg) {
        $this->logIt("INFO", $msg);
    }

    function logError($msg) {
        $this->logIt("ERROR", $msg);
    }

    function logWarning($msg) {
        $this->logIt("WARNING", $msg);
    }

    function logDebug($msg) {
        $this->logIt("DEBUG", $msg);
    }

} 