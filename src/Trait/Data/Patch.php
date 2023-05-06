<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Patch {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function patch($class, $options=[]): false|array|object
    {
        $uuid = $options['uuid'] ?? false;
        if($uuid === false){
            return false;
        }
        unset($options['uuid']);
        $name = Controller::name($class);
        $object = $this->object();
        $node = $this->record(
            $name,
            [
                'filter' => [
                    'uuid' => $uuid
                ],
                'sort' => [
                    'uuid' => 'ASC'
                ],
                'relation' => [
                    'permission:uuid'
                ]
            ]
        );
        if(!$node){
            return false;
        }
        $node = new Storage($node);
        $patch = new Storage($options);
        foreach($patch->data() as $attribute => $value){
            if(is_array($value)){
                $list = $node->get($attribute);
                if(empty($list)){
                    $list = [];
                }
                foreach($value as $record){
                    if(!in_array($record, $list, true)){
                        $list[] = $record;
                    }
                }
                $node->set($attribute, $list);
            } else {
                $node->set($attribute, $value);
            }
        }
        $expose = $this->expose_get(
            $object,
            $class,
            $class . '.' . __FUNCTION__ . '.expose'
        );
        $role = $this->record('Role', [
            'filter' => [
                'name' => 'ROLE_SYSTEM'
            ],
            'sort' => [
                'name' => 'ASC'
            ],
            'relation' => [
                'permission:uuid'
            ]
        ]);
        if(
            $expose &&
            $role
        ){
            $record = $this->expose(
                $object,
                $node,
                $expose,
                $class,
                __FUNCTION__,
                $role
            );
            ddd($record);
        }



        ddd($expose);

        return $node->data();
   }
}

