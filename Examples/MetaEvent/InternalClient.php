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
     * List sessions info
     * 
     * @var array 
     */
    protected $_sessions = [];
    
    /**
     * Constructor
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
        $session->register('com.example.getonline',     [$this, 'getOnline']);
        
        $session->subscribe('wamp.metaevent.session.on_join',  [$this, 'onSessionJoin']);
        $session->subscribe('wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);
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
    
    /**
     * Get list online
     * 
     * @return array
     */
    public function getOnline()
    {
        return [$this->_sessions];
    }

    /**
     * Handle on new session joinned
     * 
     * @param array $args
     * @param array $kwArgs
     * @param array $options
     * @return void
     * @link https://github.com/crossbario/crossbar/wiki/Session-Metaevents
     */
    public function onSessionJoin($args, $kwArgs, $options)
    {
        echo "Session {$args[0]['session']} joinned\n";
        $this->_sessions[] = $args[0];
    }
    
    /**
     * Handle on session leaved
     * 
     * @param array $args
     * @param array $kwArgs
     * @param array $options
     * @return void
     * @link https://github.com/crossbario/crossbar/wiki/Session-Metaevents
     */
    public function onSessionLeave($args, $kwArgs, $options)
    {
        if (!empty($args[0]['session'])) {
            foreach ($this->_sessions as $key => $details) {
                if ($args[0]['session'] == $details['session']) {
                    echo "Session {$details['session']} leaved\n";
                    unset($this->_sessions[$key]);
                    return;
                }
            }
        }
    }
}