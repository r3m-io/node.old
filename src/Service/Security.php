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
    public static function is_granted($class, $role, $options): bool
    {
        if(!array_key_exists('function', $options)){
            throw new Exception('Function is missing in options');
        }
        $name = Controller::name($class);
        $role = new Data($role);
        $is_permission = false;
        $is_permission_relation = false;
        $is_permission_parse = false;
        $permissions = [];
        $permissions[] = $name . '.' . $options['function'];
        if(
            array_key_exists('relation', $options) &&
            $options['relation'] === true
        ){
            $permissions[] = $name . '.' . $options['function'] . '.' . 'relation';
        }
        if(
            array_key_exists('parse', $options) &&
            $options['parse'] === true
        ){
            $permissions[] = $name . '.' . $options['function'] . '.' . 'parse';
        }
        foreach($role->get('permission') as $permission){
            $permission = new Data($permission);
            if($permission->get('name') === $name . '.' . $options['function']){
                $is_permission = true;
            }
            if(
                array_key_exists('relation', $options) &&
                $options['relation'] === true
            ){
                if($permission->get('name') === $name . '.' . $options['function'] . '.' . 'relation'){
                    $is_permission_relation = true;
                }
            } else {
                $is_permission_relation = true;
            }
            if(
                array_key_exists('parse', $options) &&
                $options['parse'] === true
            ) {
                if($permission->get('name') === $name . '.' . $options['function'] . '.' . 'parse'){
                    $is_permission_parse = true;
                }
            } else {
                $is_permission_parse = true;
            }
            if(
                $is_permission === true &&
                $is_permission_parse === true &&
                $is_permission_relation === true
            ){
                return true;
            }
        }
        throw new Exception('Security: permission denied... (' . implode(', ', $permissions) . ')');
    }
}