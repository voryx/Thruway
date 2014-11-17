<?php

namespace Voryx\ThruwayBundle\DependencyInjection;

use Symfony\Component\Security\Core\User\UserInterface;


/**
 * Class UserAware
 * @package Voryx\ThruwayBundle\DependencyInjection
 */
Trait UserAwareTrait
{

    /**
     * @var
     */
    protected $user;

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $user UserInterface
     * @return mixed|void
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }
}
