<?php

class AbortAfterHelloAuthProviderClient extends \Thruway\Authentication\AbstractAuthProviderClient {
    /**
     * @return string
     */
    public function getMethodName()
    {
        return 'abortafterhello';
    }

    public function processHello(array $args) {
        return ["FAILURE"];
    }

} 