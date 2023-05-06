<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Controller;

Trait Patch {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function patch($class, $options=[]): false|array|object
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = $this->record($name, [
            'filter' => [
                'uuid' => $options['uuid'] ?? false
            ],
            'sort' => [
                'uuid' => 'ASC'
            ],
            'relation' => [
                'permission:uuid'
            ]
        ]);
        d($class);
        d($options);
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

