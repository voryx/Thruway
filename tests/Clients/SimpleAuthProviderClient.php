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

    /**
     * Pre process AuthenticateMessage
     * Extract and validate arguments
     *
     * @param array $args
     * @return array
     */
    public function preProcessAuthenticate(array $args)
    {

        $signature = isset($args['signature']) ? $args['signature'] : null;
        $extra     = isset($args['extra']) ? $args['extra'] : null;
        $authid    = isset($args['authid']) ? $args['authid'] : "anonymous";

        if (!$signature) {
            return ["ERROR"];
        }

        if ($signature == "letMeIn") {
            return [
                "SUCCESS",
                ["authid" => $authid]
            ];
        } else {
            return array("FAILURE");

        }

    }


    public function processAuthenticate($signature, $extra = null)
    {



    }
}