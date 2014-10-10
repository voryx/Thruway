<?php


/**
 * Class SimpleAuthProviderClient
 */
class SimpleAuthProviderClient extends \Thruway\Authentication\AbstractAuthProviderClient
{

    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'simplysimple';
    }

    /**
     * Process Authenticate message
     * 
     * @param mixed $signature
     * @param mixed $extra
     * @return array
     */
    public function processAuthenticate($signature, $extra = null)
    {

        if ($signature == "letMeIn") {
            return ["SUCCESS"];
        } else {
            return ["FAILURE"];
        }

    }

}