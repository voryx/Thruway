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


} 