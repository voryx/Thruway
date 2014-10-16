<?php

namespace Voryx\ThruwayBundle;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use React\Promise\Promise;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\TransportInterface;
use Voryx\ThruwayBundle\Annotation\Register;
use Voryx\ThruwayBundle\Annotation\Subscribe;
use Voryx\ThruwayBundle\Mapping\MappingInterface;

/**
 * Class Connection
 * @package Voryx\ThruwayBundle
 */
class Connection
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
     * @var null
     */
    private $workerName;

    /**
     * @var
     */
    private $workerInstance;

    /**
     * @param ContainerInterface $container
     * @param Serializer $serializer
     * @param ResourceMapper $resourceMapper
     */
    function __construct(ContainerInterface $container, Serializer $serializer, ResourceMapper $resourceMapper)
    {
        $this->container      = $container;
        $this->serializer     = $serializer;
        $this->resourceMapper = $resourceMapper;
    }

    /**
     * @param ClientSession $session
     * @param TransportInterface $transport
     */
    public function onOpen(ClientSession $session, TransportInterface $transport)
    {

        $this->session   = $session;
        $this->transport = $transport;

        $mappings = $this->resourceMapper->getMappings($this->getWorkerName());

        /* @var $mapping MappingInterface */
        foreach ($mappings as $mapping) {
            if ($mapping->getAnnotation() instanceof Register) {

                $this->createRPC($mapping);

            } elseif ($mapping->getAnnotation() instanceof Subscribe) {

                $this->createSubscribe($mapping);
            }
        }
    }

    /**
     * @param MappingInterface $mapping
     */
    protected function createRPC(MappingInterface $mapping)
    {
        $annotation     = $mapping->getAnnotation();
        $multiRegister  = $annotation->getMultiRegister() !== null ? $annotation->getMultiRegister() : true;
        $discloseCaller = $annotation->getDiscloseCaller() !== null ? $annotation->getDiscloseCaller() : true;

        /**
         * If this isn't the first worker to be created, we can't register this RPC call again.
         */
        if ($this->getWorkerInstance() > 0 && !$multiRegister) {
            return;
        }

        $this->session->register(
            $annotation->getName(),
            function ($args, $kwargs, $details) use ($mapping, $annotation) {
                //@todo match up $kwargs to the method arguments

                try {

                    // $this->authenticateAuthId($details["authid"]);

                    $object = $this->container->get($mapping->getServiceId());

                    $data = call_user_func_array(
                        [$object, $mapping->getMethod()->getName()],
                        $this->deserialize($args, $mapping)
                    );


                    $context = new SerializationContext();
                    if ($mapping->getAnnotation()->getSerializerEnableMaxDepthChecks()) {
                        $context->enableMaxDepthChecks();
                    }

                    if ($mapping->getAnnotation()->getSerializerGroups()) {
                        $context->setGroups($mapping->getAnnotation()->getSerializerGroups());
                    }

                    /**
                     *
                     * Need to decode json so we can hand it off to the WAMP serialize.
                     * Once JSM Serializer support serializing to array, we can get rid of this.
                     * https://github.com/schmittjoh/serializer/pull/20
                     *
                     */
                    if ($data instanceof Promise) {
                        return $data->then(function ($d) use ($context) {
                            return json_decode($this->serializer->serialize($d, "json", $context));
                        });

                    }

                    return json_decode($this->serializer->serialize($data, "json", $context));

                } catch (\Exception $e) {
                    $this->container->get('logger')->critical($e->getMessage());
                    throw new \Exception("Unable to make the call: {$annotation->getName()}");
                }

            },
            ['disclose_caller' => $discloseCaller, "thruway_multiregister" => $multiRegister]
        );
    }


    /**
     * @param MappingInterface $mapping
     */
    protected function createSubscribe(MappingInterface $mapping)
    {
        $this->session->subscribe(
            $mapping->getAnnotation()->getName(),
            function ($args) use ($mapping) {

                $object = $this->container->get($mapping->getServiceId());
                call_user_func_array(
                    [$object, $mapping->getMethod()->getName()],
                    $this->deserialize($args, $mapping)
                );
            }
        );
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
     * @param $args
     * @param MappingInterface $mapping
     * @return array|bool
     */
    private function deserialize($args, MappingInterface $mapping)
    {
        try {
            $args = (array)$args;
            if (empty($args)) {
                return [];
            }

            $deserializedArgs = [];
            if (!is_array($args)) {
                $args = [$args];
            }

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
            $this->container->get('monolog.logger.emergency')->error(
                $e->getMessage()
            );
        }

        return false;
    }

    /**
     * @param $authid
     */
    private function authenticateAuthId($authid)
    {
        if ($authid !== "anonymous") {
//            $user = $this->container->get('in_memory_user_provider')->loadUserByUsername($authid);
            $user = $this->container->get('fos_user.user_manager')->findUserByUsernameOrEmail($authid);
            $this->authenticateUser($user);
        }
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
     * @return null
     */
    public function getWorkerName()
    {
        return $this->workerName;
    }

    /**
     * @param null $workerName
     */
    public function setWorkerName($workerName)
    {
        $this->workerName = $workerName;
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
    public function getWorkerInstance()
    {
        return $this->workerInstance;
    }

    /**
     * @param mixed $workerInstance
     */
    public function setWorkerInstance($workerInstance)
    {
        $this->workerInstance = $workerInstance;
    }


}