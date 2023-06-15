<?php

namespace R3m\Io\Node\Trait\Data;

use Exception;
use R3m\Io\Config;
use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Event;
use R3m\Io\Module\Sort;

Trait Put {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function put_many($class, $role, $data=[], $options=[]): array
    {
        $name = Controller::name($class);
        $object = $this->object();
        $result = [
            'list' => [],
            'count' => 0,
            'error' => [
                'list' => [],
                'count' => 0
            ]
        ];
        foreach($data as $record){
            $response = $this->put(
                $class,
                $role,
                $record,
                [
                    'is_many' => true,
                    'function' => $options['function'] ?? __FUNCTION__,
                ]
            );
            if(!$response){
                $result['error']['list'][] = false;
                $result['error']['count']++;
            }
            elseif(array_key_exists('node', $response)){
                $result['list'][] = $response['node'];
                $result['count']++;
            }
            elseif(array_key_exists('error', $response)) {
                $result['error']['list'][] = $response['error'];
                $result['error']['count']++;
            } else {
                $result['error']['list'][] = false;
                $result['error']['count']++;
            }
        }
        if($result['error']['count'] === 0){
            unset($result['error']);
        }
        return $result;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function put($class, $role, $record=[], $options=[]): false|array|object
    {
        if(is_object($record)){
            $record = (array) $record;
        }
        $uuid = $record['uuid'] ?? false;
        if($uuid === false){
            return false;
        }
        unset($record['uuid']);
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
            'function' => __FUNCTION__,
            'ramdisk'> $options['ramdisk'] ?? false
        ];
        $response = $this->record(
            $name,
            $role,
            $node_options
        );
        if(!$response){
            return false;
        }
        if(!array_key_exists('node', $response)){
            return false;
        }
        $node = new Storage($response['node']);
        $patch = new Storage($record);
        foreach($patch->data() as $attribute => $value){
            if(is_array($value)){
                $list = $node->get($attribute);
                if(empty($list) || !is_array($list)){
                    $list = [];
                } else {
                    foreach($list as $nr => $item){
                        if(
                            is_object($item) &&
                            property_exists($item, 'uuid')
                        ){
                            $list[$nr] = $item->uuid;
                        }
                    }
                }
                foreach($value as $item){
                    if(!in_array($item, $list, true)){
                        $list[] = $item;
                    }
                }
                $node->set($attribute, $list);
            } else {
                $node->set($attribute, $value);
            }
        }
        $node->set('#class', $class);
        $object->request('node', $node->data());
        $validate = $this->validate($object, $validate_url,  $class . '.put');
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
                        Event::trigger($object, 'r3m.io.node.data.put', [
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
                Event::trigger($object, 'r3m.io.node.data.put.error', [
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