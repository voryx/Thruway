<?php


namespace Voryx\ThruwayBundle\Authentication;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\Authentication\WampCraUserDbInterface;

class UserDB implements WampCraUserDbInterface
{


    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    /**
     * This should take a authid string as the argument and return
     * an associative array with authid, key, and salt.
     *
     * If salt is non-null, the key is the salted version of the password.
     *
     * @param $authid
     * @throws \Exception
     * @return array
     */
    public function get($authid)
    {
        $user = $this->container->get('in_memory_user_provider')->loadUserByUsername($authid);
        if (!$user) {
            //@todo replace this with an exception that thruway can handle
            throw new \Exception("Can't log in, bad credentials");
        }

        return ["user" => $user->getUsername(), "key" => $user->getPassword(), "salt" => $user->getSalt()];
    }
}