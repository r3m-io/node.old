<?php
namespace R3m\Io\Node\Trait;

use R3m\Io\Module\Core;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\File;
use R3m\Io\Module\Filter as Module;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Parse;

Trait Filter {

    /**
     * @throws Exception
     */
    private function filter($record=[], $filter=[], $options=[]){

        $list = [];
        $list[] = $record;
        $list = Module::list($list)->where($filter);
        if(!empty($list)){
            return $record;
        }
        return false;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function filter_nodelist($class, $role, $options): array
    {
        $name =  Controller::name($class);
        $object = $this->object();
        $list = [];
        if(!array_key_exists('url', $options)){
            return $list;
        }
        if(!array_key_exists('key', $options)){
            return $list;
        }
        if(!array_key_exists('lines', $options)){
            return $list;
        }
        if(!array_key_exists('count', $options)){
            return $list;
        }
        if(!array_key_exists('function', $options)){
            return $list;
        }
        $list_data = File::read($options['url'], File::ARRAY);
        if($list_data){
            foreach($list_data as $index => $uuid){
                $uuid = rtrim($uuid, PHP_EOL);
                if(empty($uuid)){
                    continue;
                }
                $record = (object) [];
                $record->uuid = $uuid;
                $record->{'#read'} = [];
                $record->{'#read'}['url'] = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Storage' .
                    $object->config('ds') .
                    substr($record->uuid, 0, 2) .
                    $object->config('ds') .
                    $record->uuid .
                    $object->config('extension.json')
                ;
                $record->{'#read'}['lines'] = $options['lines'];
                $record->{'#read'}['count'] = $options['count'];
                $record->{'#read'}['index'] = $index;
                $record->{'#read'} = (object) $record->{'#read'};
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                if(!property_exists($record, '#class')){
                    //need to trigger sync
                    continue;
                }
                $object_url = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Object' .
                    $object->config('ds') .
                    ucfirst($record->{'#class'}) .
                    $object->config('extension.json')
                ;
                $options_json = Core::object($options, Core::OBJECT_JSON);
                $object_data = $object->data_read($object_url, sha1($object_url . '.' . $options_json));
                $record = $this->binary_tree_relation($record, $object_data, $role, $options);
                $expose = $this->expose_get(
                    $object,
                    $record->{'#class'},
                    $record->{'#class'} . '.' . $options['function'] . '.expose'
                );
                $record = $this->expose(
                    new Storage($record),
                    $expose,
                    $record->{'#class'},
                    $options['function'],   //maybe change this (because filter has different read attributes)
                    $role
                );
                $record_data = $record->data();
                if(
                    array_key_exists('parse', $options) &&
                    $options['parse'] === true
                ){
                    $parse = new Parse($object);
                    $record->set('#role', $role);
//                    d($record);
                    //add #role, #user to record ?
                    $record_data = $parse->compile($record_data, $record);
                    ddd($record_data);
                }
                $list[] = $record_data;
            }
        }
        return $list;
    }
}