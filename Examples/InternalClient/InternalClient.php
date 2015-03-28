<?php
/**
 * This is an example of how to use the InternalClientTransportProvider
 *
 * For more information go to:
 * @see http://voryx.net/creating-internal-client-thruway/
 */

/**
 * Class InternalClient
 */
class InternalClient extends Thruway\Peer\Client
{

    /**
     * Contructor
     */
    public function __construct()
    {
        parent::__construct("realm1");
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        // TODO: now that the session has started, setup the stuff
        echo "--------------- Hello from InternalClient ------------\n";
        $session->register('com.example.getphpversion', [$this, 'getPhpVersion']);
    }

    /**
     * Handle get PHP version
     *
     * @return array
     */
    public function getPhpVersion()
    {
        return [phpversion()];
    }

}