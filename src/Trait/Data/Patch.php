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
    public function patch($class, $role, $options=[]): false|array|object
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
            $role,
            [
                'filter' => [
                    'uuid' => $uuid
                ],
                'sort' => [
                    'uuid' => 'ASC'
                ],
                'relation' => false,
                'function' => __FUNCTION__
            ]
        );
        ddd($node);
        if(!$node){
            return false;
        }
        if(!array_key_exists('node', $node)){
            return false;
        }
        $node = new Storage($node['node']);
        $patch = new Storage($options);
        //add validate
        foreach($patch->data() as $attribute => $value){
            if(is_array($value)){
                $list = $node->get($attribute);
                if(empty($list)){
                    $list = [];
                }
                elseif(is_array($list)) {
                    foreach($list as $nr => $record){
                        if(
                            is_object($record) &&
                            property_exists($record, 'uuid')
                        ){
                            $list[$nr] = $record->uuid;
                        }
                    }
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
        /*
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
        */
        if(
            $expose &&
            $role
        ){
            $record = $this->expose(
                $node,
                $expose,
                $class,
                __FUNCTION__,
                $role
            );
            if(
                $record->has('uuid') &&
                !empty($record->get('uuid'))
            ){
                //save $record
                $url = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Storage' .
                    $object->config('ds') .
                    substr($record->get('uuid'), 0, 2) .
                    $object->config('ds') .
                    $record->get('uuid') .
                    $object->config('extension.json')
                ;
                $record->write($url);
                return $record->data();
            }
        }
        return false;
   }
}

