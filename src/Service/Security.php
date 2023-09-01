<?php
namespace R3m\Io\Node\Service;

use R3m\Io\App;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data;

use Exception;

class Security extends Main
{

    public static function is_granted($class, $role, $options){
        $name = Controller::name($class);
        $role = new Data($role);
        d($class);
        d($options);
        ddd($role);
    }
}