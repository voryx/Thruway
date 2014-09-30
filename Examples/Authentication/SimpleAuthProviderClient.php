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
     * @param mixed $signature
     * @param null $extra
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