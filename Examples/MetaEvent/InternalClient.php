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
     * 
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
        $this->getCallee()->register($this->session, 'com.example.getonline',     [$this, 'getOnline']);
        
        $this->getSubscriber()->subscribe($this->session, 'wamp.metaevent.session.on_join',  [$this, 'onSessionJoin']);
        $this->getSubscriber()->subscribe($this->session, 'wamp.metaevent.session.on_leave', [$this, 'onSessionLeave']);
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