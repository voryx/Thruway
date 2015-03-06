<?php

require_once 'RelayClient.php';

/**
 * Class LastClient
 */
class LastClient extends RelayClient
{
    /**
     * Override to end the chain
     *
     * @return array|\React\Promise\Promise
     */
    public function theFunction()
    {
        $payload = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.";

        return [$payload];
    }
}