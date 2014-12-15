<?php
/**
 * This is an example of how to use the InternalClientTransportProvider
 *
 * For more information go to:
 * @see http://voryx.net/creating-internal-client-thruway/
 */
require __DIR__ . '/../bootstrap.php';

use \Thruway\Logging\Logger;
use \Thruway\Peer\Client;

/**
 * Class InternalClient
 */
class InternalClient extends Client
{
    /**
     * @var \Thruway\Peer\Router
     */
    private $router;

    /**
     * List sessions info
     * 
     * @var array 
     */
    protected $_sessions = [];
    
    /**
     * Contructor
     */
    public function __construct()
    {
        parent::__construct('realm1');
    }

    /**
     * @param \Thruway\AbstractSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        // TODO: now that the session has started, setup the stuff
        echo "--------------- Hello from InternalClient ------------\n";
        $this->getCallee()->register(
            $this->session, 
            'com.example.getphpversion', 
            [$this, 'getPhpVersion'], 
            []
        );
        
        $this->getCallee()->register(
            $this->session, 
            'com.example.getonline', 
            [$this, 'getOnline'], 
            []
        );
        
        $this->getSubscriber()->subscribe(
            $this->session, 
            'wamp.metaevent.session.on_join', 
            [$this, 'onSessionJoin'], 
            []
        );
        
        $this->getSubscriber()->subscribe(
            $this->session, 
            'wamp.metaevent.session.on_leave', 
            [$this, 'onSessionLeave'], 
            []
        );
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

    /**
     * Ping to all sessions
     * 
     * @return array
     */
    public function checkKeepAlive()
    {
        if ($this->router === null) {
            throw new \Exception("Router must be set before calling ping.");
        }

        foreach ($this->_sessions as $_details) {
            
            $sessionIdToPing = $_details['session'];
            
            Logger::info($this, ">>> PING {$sessionIdToPing}", $_details);
            /*@var $session \Thruway\Session */
            $theSession = $this->getRouter()->getSessionBySessionId($sessionIdToPing);
            if (empty($theSession)) {
                continue;
            }
            $theSession->getTransport()->ping(1)->then(
                function($res) use ($sessionIdToPing) {
                    //echo "<<< Session {$sessionIdToPing} are connecting\n";
                    Logger::info($this, "<<< Session {$sessionIdToPing} are connecting", [$res]);
                },
                function($error) use ($sessionIdToPing, $theSession) {
                    //echo "<<< Session {$sessionIdToPing} are disconnected\n";
                    Logger::info($this, "<<< Session {$sessionIdToPing} are disconnected", [$error]);
                    $theSession->shutdown();
                }
            );
        }
    }

    /**
     * @param \Thruway\Peer\Router $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return \Thruway\Peer\Router
     */
    public function getRouter()
    {
        return $this->router;
    }

}
