<?php

namespace Voryx\ThruwayBundle;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use React\Promise\Promise;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Thruway\CallResult;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\TransportInterface;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Annotation\Subscribe;
use Voryx\ThruwayBundle\Event\SessionEvent;
use Voryx\ThruwayBundle\Mapping\MappingInterface;
use Voryx\ThruwayBundle\Mapping\URIClassMapping;

/**
 * Class WampKernel
 *
 * @package Voryx\ThruwayBundle
 */
class WampKernel implements HttpKernelInterface
{

    /* @var $session ClientSession */
    private $session;

    /**
     * @var TransportInterface
     */
    private $transport;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var ResourceMapper
     */
    private $resourceMapper;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var null
     */
    private $processName;

    /**
     * @var
     */
    private $processInstance;


    /**
     * @param ContainerInterface $container
     * @param Serializer $serializer
     * @param ResourceMapper $resourceMapper
     */
    function __construct(
        ContainerInterface $container,
        Serializer $serializer,
        ResourceMapper $resourceMapper,
        EventDispatcherInterface $dispatcher
    ) {
        $this->container      = $container;
        $this->serializer     = $serializer;
        $this->resourceMapper = $resourceMapper;
        $this->dispatcher     = $dispatcher;
    }

    /**
     * @param ClientSession $session
     * @param TransportInterface $transport
     */
    public function onOpen(ClientSession $session, TransportInterface $transport)
    {
        $this->session   = $session;
        $this->transport = $transport;

        $event = new SessionEvent($session, $transport, $this->processName, $this->processInstance);
        $this->dispatcher->dispatch(WampEvents::OPEN, $event);

        //Map RPC calls and subscriptions to their controllers
        $this->mapResources();

    }

    /**
     * Go through all of the resource mappings for this worker process and create the corresponding WAMP URIs
     */
    protected function mapResources()
    {
        $mappings = $this->resourceMapper->getMappings($this->getProcessName());

        /* @var $mapping URIClassMapping */
        foreach ($mappings as $mapping) {
            if ($mapping->getAnnotation() instanceof Register) {

                $this->createRPC($mapping);

                $this->sendAuthorization($mapping, 'call');

            } elseif ($mapping->getAnnotation() instanceof Subscribe) {

                $this->createSubscribe($mapping);

                $this->sendAuthorization($mapping, 'subscribe');
            }
        }
    }

    /**
     * Register an RPC
     *
     * @param MappingInterface $mapping
     */
    protected function createRPC(MappingInterface $mapping)
    {
        /* @var $annotation Register */
        $annotation       = $mapping->getAnnotation();
        $multiRegister    = $annotation->getMultiRegister() !== null ? $annotation->getMultiRegister() : true;
        $discloseCaller   = $annotation->getDiscloseCaller() !== null ? $annotation->getDiscloseCaller() : true;
        $object           = $this->container->get($mapping->getServiceId());
        $registerCallback = $annotation->getRegisterCallback() ? [$object, $annotation->getRegisterCallback()] : null;

        /**
         * If this isn't the first worker process to be created, we can't register this RPC call again.
         */
        if ($this->getProcessInstance() > 0 && !$multiRegister) {
            return;
        }

        //RPC Options
        $callOptions = [
            'disclose_caller'          => $discloseCaller,
            "thruway_multiregister"    => $multiRegister,
            "replace_orphaned_session" => $annotation->getReplaceOrphanedSession()
        ];

        //RPC Callback
        $rpcCallback = function ($args, $kwargs, $details) use ($mapping) {
            return $this->handleRPC($args, $kwargs, $details, $mapping);
        };

        //Register the RPC Call
        $this->session->register($annotation->getName(), $rpcCallback, $callOptions)->then($registerCallback);
    }


    /**
     * Handle the RPC
     *
     * @param $args
     * @param $kwargs
     * @param $details
     * @param MappingInterface $mapping
     * @return mixed|static
     * @throws \Exception
     */
    protected function handleRPC($args, $kwargs, $details, MappingInterface $mapping)
    {
        //@todo match up $kwargs to the method arguments

        try {
            $controller     = $this->container->get($mapping->getServiceId());
            $controllerArgs = $this->deserializeArgs($args, $mapping);

            $traits = class_uses($controller);

            //Inject the User object if the UserAware trait in in use
            if (isset($traits['Voryx\ThruwayBundle\DependencyInjection\UserAwareTrait'])) {
                $user = $this->authenticateAuthId($details->authid);
                $controller->setUser($user);
            }

            //Dispatch Controller Events
            $this->dispatchControllerEvents($controller, $mapping);

            //Call Controller
            $rawResult = call_user_func_array([$controller, $mapping->getMethod()->getName()], $controllerArgs);

            //Create a serialization context
            $context = $this->createSerializationContext($mapping);

            //Do clean on the controller
            $this->cleanup($controller);

            if ($rawResult instanceof Promise) {
                return $rawResult->then(function ($d) use ($context) {
                    //If the data is a CallResult, we only want to serialize the first argument
                    $d = $d instanceof CallResult ? [$d[0]] : $d;
                    return json_decode($this->serializer->serialize($d, "json", $context));
                });
            } else {
                return json_decode($this->serializer->serialize($rawResult, "json", $context));
            }

        } catch (\Exception $e) {
            $this->container->get('logger')->critical($e->getMessage());
            throw new \Exception("Unable to make the call: {$mapping->getAnnotation()->getName()}");
        }
    }

    /**
     * Subscribe to a topic
     *
     * @param MappingInterface $mapping
     */
    protected function createSubscribe(MappingInterface $mapping)
    {
        $topic = $mapping->getAnnotation()->getName();

        $subscribeCallback = function ($args) use ($mapping) {
            $this->handleEvent($args, $mapping);
        };

        //Subscribe to a topic
        $this->session->subscribe($topic, $subscribeCallback);
    }

    /**
     * Handle an subscription Event
     *
     * @param $args
     * @param MappingInterface $mapping
     */
    protected function handleEvent($args, MappingInterface $mapping)
    {
        $controller     = $this->container->get($mapping->getServiceId());
        $controllerArgs = $this->deserializeArgs($args, $mapping);

        //Call Controller
        call_user_func_array([$controller, $mapping->getMethod()->getName()], $controllerArgs);

        $this->cleanup($controller);
    }

    /**
     * Create serialization context with settings taken from the controller's annotation
     *
     * @param MappingInterface $mapping
     * @return SerializationContext
     */
    protected function createSerializationContext(MappingInterface $mapping)
    {
        $context = new SerializationContext();

        if ($mapping->getAnnotation()->getSerializerEnableMaxDepthChecks()) {
            $context->enableMaxDepthChecks();
        }

        if ($mapping->getAnnotation()->getSerializerGroups()) {
            $context->setGroups($mapping->getAnnotation()->getSerializerGroups());
        }

        if ($mapping->getAnnotation()->getSerializerSerializeNull()) {
            $context->setSerializeNull(true);
        }

        return $context;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
        $this->client->on('open', [$this, 'onOpen']);
    }


    /**
     * @return mixed
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return mixed
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Deserialize Controller arguments
     *
     * //@todo add ability to configure the deserialization context
     *
     * @param $args
     * @param MappingInterface $mapping
     * @return array|bool
     */
    private function deserializeArgs($args, MappingInterface $mapping)
    {
        try {
            $args = (array)$args;

            if ($this->isAssoc($args)) {
                $args = [$args];
            }

            $deserializedArgs = [];

            if ($mapping->getMethod()->getNumberOfRequiredParameters() > count($args)) {
                throw new \Exception(
                    "Not enough parameters for '{$mapping->getMethod()->class}::{$mapping->getMethod()->getName()}'"
                );
            }

            $params = $mapping->getmethod()->getParameters();

            /* @var $param \ReflectionParameter */
            foreach ($params as $key => $param) {

                $className = null;
                if ($param->getClass() && $param->getClass()->getName()) {
                    if (!$param->getClass()->isInstantiable()) {
                        $this->container->get('monolog.logger.emergency')->error(
                            "Can't deserialize to '{$param->getClass()->getName()}', because it is not instantiable."
                        );

                        throw new \Exception(
                            "Can't deserialize to '{$param->getClass()->getName()}', because it is not instantiable."
                        );
                    }

                    $className          = $param->getClass()->getName();
                    $deserializedArgs[] = $this->serializer->deserialize(json_encode($args[$key]), $className, "json");

                } else {
                    $deserializedArgs[] = $args[$key];
                }

            }

            return $deserializedArgs;

        } catch (\Exception $e) {
            $this->container->get('monolog.logger.emergency')->error($e->getMessage());
        }

        return [];
    }

    /**
     * @param $authid
     * @return bool | UserInterface
     */
    private function authenticateAuthId($authid)
    {
        if ($authid !== "anonymous") {
            $config = $this->container->getParameter('voryx_thruway');

            if ($this->container->has($config['user_provider'])) {
                $user = $this->container->get($config['user_provider'])->findUserByUsernameOrEmail($authid);
                $this->authenticateUser($user);
                return $user;
            }
        }

        return false;
    }

    /**
     * @param UserInterface $user
     */
    private function authenticateUser(UserInterface $user)
    {
        $providerKey = 'thruway';
        $token       = new UsernamePasswordToken($user, null, $providerKey, $user->getRoles());
        $this->container->get('security.context')->setToken($token);
    }

    /**
     * @param $controller
     * @param URIClassMapping $mapping
     */
    private function dispatchControllerEvents($controller, URIClassMapping $mapping)
    {
        $request  = new Request();
        $callable = [$controller, $mapping->getMethod()->getName()];
        $event    = new FilterControllerEvent($this, $callable, $request, self::MASTER_REQUEST);
        $this->dispatcher->dispatch(KernelEvents::CONTROLLER, $event);
    }

    /**
     * @return null
     */
    public function getProcessName()
    {
        return $this->processName;
    }

    /**
     * @param null $processName
     */
    public function setProcessName($processName)
    {
        $this->processName = $processName;
    }

    /**
     * @return ResourceMapper
     */
    public function getResourceMapper()
    {
        return $this->resourceMapper;
    }

    /**
     * @return mixed
     */
    public function getProcessInstance()
    {
        return $this->processInstance;
    }

    /**
     * @param mixed $processInstance
     */
    public function setProcessInstance($processInstance)
    {
        $this->processInstance = $processInstance;
    }


    /**
     * @param $arr
     * @return bool
     */
    private function isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }


    /**
     *  Cleanup
     * @param $controller
     */
    private function cleanup($controller)
    {
        unset ($controller);

        //Clear out any stuff that doctrine has cached
        if ($this->container->has('doctrine')) {
            $this->container->get('doctrine')->getManager()->clear();
        }
    }

    /**
     * //Not tested
     *
     * @param URIClassMapping $mapping
     * @param $action
     */
    public function sendAuthorization(URIClassMapping $mapping, $action)
    {
        $uri   = $mapping->getAnnotation()->getName();
        $roles = $this->extractRoles($mapping);

        foreach ($roles as $role) {

            $this->session->call("add_authorization_rule", [
                [
                    "role"   => $role,
                    "action" => $action,
                    "uri"    => $uri,
                    "allow"  => true
                ]
            ])->then(
                function ($r) {
                    echo "Sent authorization\n";
                },
                function ($msg) {
                    echo "Failed to send authorization\n";
                }
            );
        }
    }

    /**
     * @param URIClassMapping $mapping
     * @return array
     */
    protected function extractRoles(URIClassMapping $mapping)
    {
        $roles = [];

        $securityAnnotation = $mapping->getSecurityAnnotation();

        if ($securityAnnotation) {

            $expression = $securityAnnotation->getExpression();

            preg_match_all("/'(.*?)'/", $expression, $matches);
            $roles = $matches[1];
        }

        return $roles;

    }


    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        // TODO: Implement handle() method.
    }
}
