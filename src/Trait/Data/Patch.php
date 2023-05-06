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
        $node = $this->record($name, [
            'filter' => [
                'uuid' => $uuid
            ],
            'sort' => [
                'uuid' => 'ASC'
            ],
            'relation' => [
                'permission:uuid'
            ]
        ]);
        if(!$node){
            return false;
        }
        $node = new Storage($node);
        $patch = new Storage($options);

        d($class);
        d($patch);
        ddd($node);
        /*
        $data = $object->data_read($url);
        if(!$data){
            return false;
        }
        */





        $list = $data->get($class);
        if(empty($list)){
            $list = [];
        }
        $uuid = $node->get('uuid');
        $is_found = false;
        $record = false;
        foreach($list as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('uuid', $record) &&
                $record['uuid'] === $uuid
            ){
                foreach($node->data() as $attribute => $value){
                    if($attribute === 'uuid'){
                        continue;
                    }
                    $list[$nr][$attribute] = $value;
                }
                $is_found = true;
                $record = $list[$nr];
                break;
            }
            elseif(
                is_object($record) &&
                property_exists($record,'uuid') &&
                $record->uuid === $uuid
            ){
                foreach($node->data() as $attribute => $value){
                    if($attribute === 'uuid'){
                        continue;
                    }
                    $record->{$attribute} = $value;
                }
                $is_found = true;
                break;
            }
        }
        if($is_found){
            $data->set($class, $list);
            $data->write($url);
            return $record;
        }
        return false;
    }

}

