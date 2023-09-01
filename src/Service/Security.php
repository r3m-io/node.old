<?php
namespace R3m\Io\Node\Service;

use R3m\Io\App;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data;

use Exception;

class Security extends Main
{

    /**
     * @throws Exception
     */
    public static function is_granted($class, $role, $options){
        if(!array_key_exists('function', $options)){
            throw new Exception('Function is missing in options');
        }
        $name = Controller::name($class);
        $role = new Data($role);

        d($name . '.' . $options['function']);

        foreach($role->get('permission') as $permission){
            $permission = new Data($permission);
            if($permission->get('name') === $name . '.' . $options['function']){
                return true;
            }
        }
    }
}