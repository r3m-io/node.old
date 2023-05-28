<?php

namespace R3m\Io\Node\Trait\Data;

use Exception;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Event;

Trait Patch {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function patch($class, $role, $options=[]): false|array
    {
        $uuid = $options['uuid'] ?? false;
        if($uuid === false){
            return false;
        }
        unset($options['uuid']);
        $name = Controller::name($class);
        $object = $this->object();
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_validate = $dir_node .
            'Validate'.
            $object->config('ds')
        ;
        $validate_url =
            $dir_validate .
            $name .
            $object->config('extension.json');
        $node_options = [
            'filter' => [
                'uuid' => $uuid
            ],
            'sort' => [
                'uuid' => 'ASC'
            ],
            'relation' => false,
            'function' => __FUNCTION__
        ];
        $node = $this->record(
            $name,
            $role,
            $node_options
        );
        if(!$node){
            return false;
        }
        if(!array_key_exists('node', $node)){
            return false;
        }
        $node = new Storage($node['node']);
        $patch = new Storage($options);
        foreach($patch->data() as $attribute => $value){
            if(is_array($value)){
                $list = $node->get($attribute);
                if(empty($list) || !is_array($list)){
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
        $object->request('node', $node->data());
        $validate = $this->validate($object, $validate_url,  $class . '.patch');
        $response = [];
        if($validate){
            if($validate->success === true){
                $expose = $this->expose_get(
                    $object,
                    $class,
                    $class . '.' . __FUNCTION__ . '.expose'
                );
                if(
                    $expose &&
                    $role
                ){
                    $record = $this->expose(
                        new Storage($object->request('node')),
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
                        $response['node'] = Core::object($record->data(), Core::OBJECT_ARRAY);
                        Event::trigger($object, 'r3m.io.node.data.patch', [
                            'class' => $class,
                            'options' => $options,
                            'url' => $url,
                            'node' => $record->data(),
                        ]);
                    } else {
                        throw new Exception('Make sure, you have the right permission (' . $class . '.' . __FUNCTION__ . ')');
                    }
                }
            } else {
                $response['error'] = $validate->test;
                Event::trigger($object, 'r3m.io.node.data.patch.error', [
                    'class' => $class,
                    'options' => $options,
                    'node' => $object->request('node'),
                    'error' => $validate->test,
                ]);
            }
        } else {
            throw new Exception('Cannot validate node at: ' . $validate_url);
        }
        return $response;
   }
}

