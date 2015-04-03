<?php


namespace Voryx\ThruwayBundle\Authentication;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Thruway\Authentication\WampCraUserDbInterface;
use Thruway\Logging\Logger;

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
        try {
            $userProvider = $this->container->getParameter('voryx_thruway')['user_provider'];

            if (null === $userProvider) {
                throw new \Exception('voryx_thruway.user_provider must be set.');
            }

            $user = $this->container->get($userProvider)->loadUserByUsername($authid);
            if (!$user) {
                throw new \Exception("Can't log in, bad credentials");
            }

            return ["user" => $user->getUsername(), "key" => $user->getPassword(), "salt" => $user->getSalt()];

        } catch (\Exception $e) {

            Logger::error($this, $e->getMessage());
            return false;
        }
    }
}
