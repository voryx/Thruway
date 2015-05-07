<?php


use Thruway\Message\HelloMessage;

class QueryParamAuthProviderClient extends \Thruway\Authentication\AbstractAuthProviderClient
{

    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'query_param_auth';
    }


    /**
     * Process HelloMessage
     *
     * @param array $args
     * @return array<string|array>
     */
    public function processHello(array $args)
    {
        if (!isset($args[0])) {
            return ["FAILURE"];
        }

        $helloMessage = \Thruway\Message\Message::createMessageFromArray($args[0]);

        if ($helloMessage instanceof HelloMessage
          && isset($helloMessage->getDetails()->transport->query_params->token)
          && $helloMessage->getDetails()->transport->query_params->token === 'sadfsaf'
        ) {

            return ["NOCHALLENGE", ["authid" => "joe"]];
        }

        return ["FAILURE"];

    }
}