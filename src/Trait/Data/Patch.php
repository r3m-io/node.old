<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\App;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Event;
use R3m\Io\Module\Sort;

use R3m\Io\Node\Service\Security;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Patch {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function patch_many($class, $role, $data=[], $options=[]): array
    {
        $name = Controller::name($class);
        $object = $this->object();
        $result = [
            'list' => [],
            'count' => 0,
            'error' => [
                'list' => [],
                'uuid' => [],
                'count' => 0
            ]
        ];
        if(!array_key_exists('function', $options)){
            $options['function'] = __FUNCTION__;
        }
        $options['relation'] = false;
        if(!Security::is_granted(
            $class,
            $role,
            $options
        )){
            return $result;
        }
        foreach($data as $record){
            $response = $this->patch(
                $class,
                $role,
                $record,
                [
                    'is_many' => true,
                    'function' => $options['function'],
                    'relation' => $options['relation'],
                ]
            );
            if(
                is_object($record) &&
                property_exists($record, 'uuid')
            ){
                $uuid = $record->uuid;
            }
            elseif(
                is_array($record) &&
                array_key_exists('uuid', $record)
            ){
                $uuid = $record['uuid'];
            }
            if(!$response){
                $record['error']['uuid'][] = $uuid;
                $result['error']['list'][] = false;
                $result['error']['count']++;
            }
            elseif(
                array_key_exists('node', $response) &&
                array_key_exists('uuid', $response['node'])
            ){
                $result['list'][] = $response['node']['uuid'];
                $result['count']++;
            }
            elseif(array_key_exists('error', $response)) {
                $record['error']['uuid'][] = $uuid;
                $result['error']['list'][] = $response['error'];
                $result['error']['count']++;
            } else {
                $record['error']['uuid'][] = $uuid;
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
    public function patch($class, $role, $record=[], $options=[]): false|array|object
    {
        if(is_array($record)){
            $record = Core::object($record, Core::OBJECT_OBJECT);
        }
        if(!array_key_exists('function', $options)){
            $options['function'] = __FUNCTION__;
        }
        $options['relation'] = false;
        if(!Security::is_granted(
            $class,
            $role,
            $options
        )){
            return false;
        }
        $uuid = $record->uuid ?? false;
        if($uuid === false){
            return false;
        }
//        unset($record->uuid);
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
            'relation' => $options['relation'],
            'function' => $options['function'],
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
        $object->request('node', $record);
        $validate = $this->validate($object, $validate_url,  $name . '.' . __FUNCTION__);
        $node = new Storage($response['node']);
        $patch = new Storage($record);
        $flags = new Storage(App::flags($object));
        $is_array_prepend = $flags->get('array.prepend') ?? false;
        $is_array_empty = $flags->get('array.empty') ?? false;
        foreach($patch->data() as $attribute => $value){
            if(is_array($value)){
                $list = $node->get($attribute);
                if(
                    empty($list) ||
                    !is_array($list) ||
                    empty($value) ||
                    $is_array_empty
                ){
                    $list = [];
                } else {
                    foreach($list as $nr => $item){
                        if(
                            is_object($item) &&
                            property_exists($item, 'uuid')
                        ){
                            $list[$nr] = $item->uuid;
                        }
                        elseif(
                            is_array($item) &&
                            array_key_exists('uuid', $item)
                        ){
                            $list[$nr] = $item['uuid'];
                        }
                    }
                }
                foreach($value as $item){
                    if(Core::is_uuid($item)){
                        /*
                         * when a list contains uuid from a relation
                         * these uuids need to be unique in the list
                         * only if item is uuid
                         */
                        if(!in_array($item, $list, true)){
                            if($is_array_prepend){
                                array_unshift($list, $item);
                            } else {
                                $list[] = $item;
                            }
                        }
                    }
                    elseif($is_array_prepend) {
                        array_unshift($list, $item);
                    } else {
                        $list[] = $item;
                    }
                }
                $node->set($attribute, $list);
            }
            elseif(is_object($value)){
                $node->set($attribute, Core::object_merge($node->get($attribute), $value));
                $node->remove_null();
            }
            else {
                $node->set($attribute, $value);
            }
        }
        $node->set('#class', $name);
        $response = [];
        if($validate){
            if($validate->success === true){
                $expose = $this->expose_get(
                    $object,
                    $name,
                    $name . '.' . __FUNCTION__ . '.expose'
                );
                if(
                    $expose &&
                    $role
                ){
                    $record_expose = $this->expose(
                        $node,
                        $expose,
                        $name,
                        __FUNCTION__,
                        $role
                    );
                    if(
                        $record_expose->has('uuid') &&
                        !empty($record_expose->get('uuid'))
                    ){
                        //save $record
                        $url = $object->config('project.dir.data') .
                            'Node' .
                            $object->config('ds') .
                            'Storage' .
                            $object->config('ds') .
                            substr($record_expose->get('uuid'), 0, 2) .
                            $object->config('ds') .
                            $record_expose->get('uuid') .
                            $object->config('extension.json')
                        ;
                        $record_expose->write($url);
                        $response['node'] = Core::object($record_expose->data(), Core::OBJECT_ARRAY);
                        Event::trigger($object, 'r3m.io.node.data.patch', [
                            'class' => $name,
                            'options' => $options,
                            'url' => $url,
                            'node' => $node,
                        ]);
                    } else {
                        throw new Exception('Make sure, you have the right permission (' . $name . '.' . __FUNCTION__ . ')');
                    }
                }
            } else {
                $response['error'] = $validate->test;
                Event::trigger($object, 'r3m.io.node.data.patch.error', [
                    'class' => $name,
                    'options' => $options,
                    'node' => $node,
                    'error' => $validate->test,
                ]);
            }
        } else {
            throw new Exception('Cannot validate node at: ' . $validate_url);
        }
        return $response;
    }
}