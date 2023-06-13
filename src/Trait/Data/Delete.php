<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\File;

Trait Delete {
    public function delete($class, $role, $options=[]): bool
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $url = $dir_class . 'Data.json';
        $data = $object->data_read($url);
        if(!$data){
            return false;
        }
        $list = $data->get($name);
        if(empty($list)){
            $list = [];
        }
        $uuid = $node->get('uuid');

        ddd($role);

        foreach($list as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('uuid', $record) &&
                $record['uuid'] === $uuid
            ){
                unset($list[$nr]);
                break;
            }
            elseif(
                is_object($record) &&
                property_exists($record,'uuid') &&
                $record->uuid === $uuid
            ){
                unset($list[$nr]);
                break;
            }
        }
        $result = [];
        foreach($list as $record){
            $result[] = $record;
        }
        $data->set($class, $result);
        $data->write($url);
        $url_node = $dir_node .
            'Storage' .
            $object->config('ds') .
            substr($uuid, 0, 2) .
            $object->config('ds') .
            $uuid .
            $object->config('extension.json')
        ;
        File::delete($url_node);
        return true;
    }
}