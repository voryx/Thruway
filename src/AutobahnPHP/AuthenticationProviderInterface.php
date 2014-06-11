<?php
/**
 * Created by PhpStorm.
 * User: daviddan
 * Date: 6/11/14
 * Time: 12:19 AM
 */

namespace AutobahnPHP;


interface AuthenticationProviderInterface
{

    /**
     * @return boolean
     */
    public function authenticate($token);

    /**
     * @return boolean
     */
    public function supports($type);

    /**
     * @return mixed
     */
    public function getAuthenticationId();

    /**
     * @return mixed
     */
    public function getAuthenticationRole();

    /**
     * @return mixed
     */
    public function getAuthenticationMethod();

} 