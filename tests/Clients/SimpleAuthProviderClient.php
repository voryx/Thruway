<?php


class SimpleAuthProviderClient extends \Thruway\Authentication\AbstractAuthProviderClient
{

    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'simplysimple';
    }

    public function processAuthenticate($signature, $extra = null)
    {

        if ($signature == "letMeIn") {
            return [
                "SUCCESS",
                ["authid" => "me@example.com"]
            ];
        } else {
            return array("FAILURE");

        }

    }
}