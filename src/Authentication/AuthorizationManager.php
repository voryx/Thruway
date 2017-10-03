<?php

namespace Thruway\Authentication;

use Ratchet\Wamp\Exception;
use React\EventLoop\LoopInterface;
use Thruway\Common\Utils;
use Thruway\Event\MessageEvent;
use Thruway\Event\NewRealmEvent;
use Thruway\Message\ActionMessageInterface;
use Thruway\Message\ErrorMessage;
use Thruway\Module\RealmModuleInterface;
use Thruway\Module\RouterModuleClient;
use Thruway\Peer\RouterInterface;
use Thruway\Result;
use Thruway\Session;

/**
 * Class AuthorizationManager
 * @package Thruway\Authentication
 */
class AuthorizationManager extends RouterModuleClient implements RealmModuleInterface
{
    /**
     * @var bool
     */
    private $ready;

    /**
     * @var array;
     */
    private $rules;

    /**
     * @param string $realm
     * @param null $loop
     */
    public function __construct($realm, $loop = null)
    {
        parent::__construct($realm, $loop);

        $this->setReady(false);

        $this->rules = [];

        $this->flushAuthorizationRules();
    }

    /**
     * Listen for Router events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'new_realm' => ['handleNewRealm', 10]
        ];
    }

    /**
     * @param \Thruway\Event\NewRealmEvent $newRealmEvent
     */
    public function handleNewRealm(NewRealmEvent $newRealmEvent)
    {
        $realm = $newRealmEvent->realm;

        if ($realm->getRealmName() === $this->getRealm()) {
            $realm->addModule($this);
        }
    }

    /** @return array */
    public function getSubscribedRealmEvents()
    {
        return [
            'PublishMessageEvent'   => ['handleMessage', 100],
            'SubscribeMessageEvent' => ['handleMessage', 100],
            'RegisterMessageEvent'  => ['handleMessage', 100],
            'CallMessageEvent'      => ['handleMessage', 100],
        ];
    }

    /**
     * @param \Thruway\Event\MessageEvent $messageEvent
     */
    public function handleMessage(MessageEvent $messageEvent)
    {
        if (!$this->isAuthorizedTo($messageEvent->session, $messageEvent->message)) {
            $messageEvent->session->sendMessage(ErrorMessage::createErrorMessageFromMessage($messageEvent->message, 'wamp.error.not_authorized'));
            $messageEvent->stopPropagation();
        }

    }

    /**
     * Gets called when the module is initialized in the router
     *
     * @inheritdoc
     */
    public function initModule(RouterInterface $router, LoopInterface $loop)
    {
        parent::initModule($router, $loop);
    }

    /**
     * Check to see if an action is authorized on a specific uri given the
     * context of the session attempting the action
     *
     * actionMsg should be an instance of: register, call, subscribe, or publish messages
     *
     * @param Session $session
     * @param ActionMessageInterface $actionMsg
     * @throws \Exception
     * @return boolean
     */
    public function isAuthorizedTo(Session $session, ActionMessageInterface $actionMsg)
    {
        // authorization
        $action = $actionMsg->getActionName();
        $uri    = $actionMsg->getUri();

        $authenticationDetails = $session->getAuthenticationDetails();

        // admin can do anything - pretty important
        // if this isn't here - then we can't setup any other rules
        if ($authenticationDetails->hasAuthRole('admin')) {
            return true;
        }

        if (!$this->isReady()) {
            return false;
        }

        $rolesToCheck = ['default'];
        if (count($authenticationDetails->getAuthRoles()) > 0) {
            $rolesToCheck = array_merge($rolesToCheck, $authenticationDetails->getAuthRoles());
        }

        return $this->isAuthorizedByRolesActionAndUri($rolesToCheck, $action, $uri);
    }

    /**
     * @param $rolesToCheck
     * @param $action
     * @param $uri
     * @return bool|mixed
     */
    private function isAuthorizedByRolesActionAndUri($rolesToCheck, $action, $uri)
    {
        if (!in_array('default', $rolesToCheck, true)) {
            $rolesToCheck = array_merge(['default'], $rolesToCheck);
        }

        $ruleUri = $action . '.' . $uri;

        $uriParts = explode('.', $ruleUri);

        $matchable = ['.'];
        $building  = '';
        foreach ($uriParts as $part) {
            $building    .= $part . '.';
            $matchable[] = $building;
        }

        // remove the last dot for exact matches
        $matchable[count($matchable) - 1] = substr($building, 0, strlen($building) - 1);

        // flip the array so we can do intersections on the keys
        $matchable = array_flip($matchable);

        $matches = [];

        foreach ($rolesToCheck as $role) {
            if (isset($this->rules[$role])) {
                // if there are identical matches - we use the
                // most restrictive one - we have to set this before the merge, otherwise
                // it will default the value to the first value
                $m       = array_intersect_key($this->rules[$role], $matchable);
                $overlap = array_intersect_key($matches, $m);
                foreach ($overlap as $k => $v) {
                    // if either is false - set both to false
                    if (!$matches[$k] || !$m[$k]) {
                        $matches[$k] = false;
                        $m[$k]       = false;
                    }
                }
                $matches = array_merge($matches, $m);
            }
        }

        // sort the list by length
        $keys = array_map('strlen', array_keys($matches));
        array_multisort($keys, SORT_DESC, $matches);

        $allow = false;
        // grab the top one
        if (!empty($matches)) {
            reset($matches);
            $allow = current($matches);
        }

        return $allow;
    }

    /**
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $promises   = [];
        $promises[] = $this->getCallee()->register($session, 'add_authorization_rule', [$this, 'addAuthorizationRule']);
        $promises[] = $this->getCallee()->register($session, 'remove_authorization_rule',
            [$this, 'removeAuthorizationRule']);
        $promises[] = $this->getCallee()->register($session, 'flush_authorization_rules',
            [$this, 'flushAuthorizationRules']);
        $promises[] = $this->getCallee()->register($session, 'get_authorization_rules',
            [$this, 'getAuthorizationRules']);
        $promises[] = $this->getCallee()->register($session, 'test_authorization',
            [$this, 'testAuthorization']);

        $pAll = \React\Promise\all($promises);

        $pAll->then(
            function () {
                $this->setReady(true);
            },
            function () {
                $this->setReady(false);
            }
        );
    }

    /**
     * @param $uri
     * @return bool
     */
    public static function isValidRuleUri($uri)
    {
        if ($uri === '') {
            return true;
        }

        $uriToCheck = $uri;
        if (substr($uriToCheck, strlen($uriToCheck) - 1, 1) == '.') {
            $uriToCheck = substr($uriToCheck, 0, strlen($uriToCheck) - 1);
        }

        return Utils::uriIsValid($uriToCheck);
    }

    /**
     * @param $args
     * @return \stdClass|bool
     */
    private function getRuleFromArgs($args)
    {
        if (!is_array($args)) {
            return false;
        }
        if (!is_object($args[0])) {
            return false;
        }

        $rule = $args[0];

        if (isset($rule->role) &&
            isset($rule->action) &&
            isset($rule->uri) &&
            isset($rule->allow)
        ) {
            if ($this->isValidAction($rule->action) &&
                static::isValidRuleUri($rule->uri) && Utils::uriIsValid($rule->role)
            ) {
                if ($rule->allow === true || $rule->allow === false) {
                    return (object)[
                        'action' => $rule->action,
                        'uri'    => $rule->uri,
                        'role'   => $rule->role,
                        'allow'  => $rule->allow
                    ];
                }
            }
        }

        return false;
    }

    /**
     *
     * rules look like (JSON)
     * {
     *    'role': 'some_role',
     *    'action': 'publish',
     *    'uri': 'some.uri',
     *    'allow': true
     * }
     *
     * Should be $args[0]
     *
     * @param $args
     * @return string
     */
    public function addAuthorizationRule($args)
    {
        $rule = $this->getRuleFromArgs($args);

        if ($rule === false) {
            return 'ERROR';
        }

        $role      = $rule->role;
        $actionUri = $rule->action . '.' . $rule->uri;
        $allow     = $rule->allow;

        if (!isset($this->rules[$role])) {
            $this->rules[$role] = [];
        }

        if (isset($this->rules[$role][$actionUri])) {
            return 'ERROR';
        }

        $this->rules[$role][$actionUri] = $allow;

        return 'ADDED';
    }

    /**
     * @param $args
     * @throws Exception
     */
    public function removeAuthorizationRule($args)
    {
        throw new Exception('remove_authorization_rule is not implemented yet');
    }

    /**
     * @param bool $allowByDefault
     * @return string
     */
    public function flushAuthorizationRules($allowByDefault = false)
    {
        // $allowByDefault will be an array if it comes from a WAMP call
        if (is_array($allowByDefault) && isset($allowByDefault[0])) {
            $allowByDefault = $allowByDefault[0];
        }

        if ($allowByDefault !== true && $allowByDefault !== false) {
            return 'ERROR';
        }

        $this->rules = [];
        // default startup rules

        // indexes in the rules match the role we are checking for
        // we give the longest uri precedence
        $this->rules['default'] = [
            '.' => $allowByDefault
        ];

        return 'OK';
    }

    /**
     * @return Result
     */
    public function getAuthorizationRules()
    {
        $result = new Result([(array)$this->rules]);

        return $result;
    }

    /**
     * Arguments need to be [['role1', 'role2'], 'publish|subscribe|register|call', 'my.uri']
     *
     * @param $args
     * @return bool|mixed
     */
    public function testAuthorization($args)
    {
        if (is_array($args) && count($args) < 3) {
            return false;
        }
        $roles = $args[0];
        if (is_string($roles)) {
            $roles = [$roles];
        }

        $action = $args[1];
        if (!static::isValidAction($action)) {
            return false;
        }

        $uriToCheck = $args[2];
        if (!Utils::uriIsValid($uriToCheck)) {
            return false;
        }

        return $this->isAuthorizedByRolesActionAndUri($roles, $action, $uriToCheck);
    }

    /**
     * @param $action
     * @return bool
     */
    public static function isValidAction($action)
    {
        return in_array($action, ['publish', 'subscribe', 'register', 'call'], true);
    }

    /**
     * @return boolean
     */
    public function isReady()
    {
        return $this->ready;
    }

    /**
     * @param boolean $ready
     */
    public function setReady($ready)
    {
        $this->ready = $ready;
    }
}
