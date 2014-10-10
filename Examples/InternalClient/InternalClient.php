<?php
/**
 * This is an example of how to use the InternalClientTransportProvider
 *
 * For more information go to:
 * @see http://voryx.net/creating-internal-client-thruway/
 */

require "../bootstrap.php";

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
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        // TODO: now that the session has started, setup the stuff
        echo "--------------- Hello from InternalClient ------------\n";
        $this->getCallee()->register($this->session, 'com.example.getphpversion', [$this, 'getPhpVersion']);
    }

    /**
     * Override to make sure we do nothing
     */
    public function start()
    {
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