<?php

namespace R3m\Io\Node\Service;

use R3m\Io\App;
use R3m\Io\Node\Model\Role as Model;
class Role {

    public static function create($role): Model
    {
        $model = new Model();
        if(is_array($role) || is_object($role)){
            foreach($role as $attribute => $value){
                if(substr($attribute, 0, 1) === '#'){
                    continue;
                }
                $model->{$attribute}($value);
            }
        }
        return $model;
    }
}