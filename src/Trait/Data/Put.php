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
        $result = [];
        ddd($data);
        foreach($data as $record){
            $put = $this->put(
                $class,
                $role,
                $record,
                [
                    'is_many' => true,
                    'function' => $options['function'] ?? __FUNCTION__,
                ]
            );
            d($record);
            ddd($put);
            $result[] = $put;
        }
        /*
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_meta = $dir_node .
            'Meta'.
            $object->config('ds')
        ;
        $dir_binary_search = $dir_node .
            'BinarySearch'.
            $object->config('ds')
        ;
        $dir_binary_search_class = $dir_binary_search .
            $name .
            $object->config('ds')
        ;
        $dir_binary_search =
            $dir_binary_search_class .
            'Asc' .
            $object->config('ds')
        ;
        $this->dir($object,
            [
                'node' => $dir_node,
                'meta' => $dir_meta,
                'binary_search_class' => $dir_binary_search_class,
                'binary_search' => $dir_binary_search,
            ]
        );
        $binary_search_url =
            $dir_binary_search .
            'Uuid' .
            $object->config('extension.json');
        $meta_url = $dir_meta . $name . $object->config('extension.json');
        $binarySearch = $object->data_read($binary_search_url);
        if (!$binarySearch) {
            $binarySearch = new Storage();
        }
        $list = $binarySearch->data($class);
        if (empty($list)) {
            $list = [];
        }
        if (is_object($list)) {
            $list_result = [];
            foreach ($list as $key => $record) {
                $list_result[] = $record;
                unset($list[$key]);
            }
            $list = $list_result;
            unset($list_result);
        }
        /*
        foreach($result as $nr => $node) {
            if(is_array($node)){
                if (array_key_exists('error', $node)) {
                    continue;
                }
                if(!array_key_exists('node', $node)){
                    continue;
                }
                if(!array_key_exists('uuid', $node['node'])) {
                    continue;
                }
                $item = [
                    'uuid' => $node['node']['uuid']
                ];
                $list[] = (object) $item;
            }
        }
        $list = Sort::list($list)->with([
            'uuid' => 'ASC',
        ], [
            'key_reset' => true,
        ]);
        $binarySearch->delete($class);
        $binarySearch->data($class, $list);
        $count = 0;
        foreach ($binarySearch->data($class) as $record) {
            $record->{'#index'} = $count;
            $count++;
        }
        $lines = $binarySearch->write($binary_search_url, 'lines');

        if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
            $command = 'chmod 666 ' . $binary_search_url;
            exec($command);
        }
        if ($object->config(Config::POSIX_ID) === 0) {
            $command = 'chown www-data:www-data ' . $binary_search_url;
            exec($command);
        }
        */
        /*
        $meta = $object->data_read($meta_url);
        if (!$meta) {
            $meta = new Storage();
        }
        $key = [
            'property' => [
                'uuid'
            ]
        ];
        $property = [];
        $property[] = 'uuid';
        $key = sha1(Core::object($key, Core::OBJECT_JSON));
        $meta->set('Sort.' . $class . '.' . $key . '.property', $property);
        $meta->set('Sort.' . $class . '.' . $key . '.lines', $lines);
        $meta->set('Sort.' . $class . '.' . $key . '.count', $count);
        $meta->set('Sort.' . $class . '.' . $key . '.url.asc', $binary_search_url);
        $meta->write($meta_url);
        if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
            $command = 'chmod 666 ' . $meta_url;
            exec($command);
        }
        if ($object->config(Config::POSIX_ID) === 0) {
            $command = 'chown www-data:www-data ' . $meta_url;
            exec($command);
        }
        */
        return $result;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function put($class, $role, $record=[], $options=[]): false|array|object
    {
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
            'function' => __FUNCTION__
        ];
        $response = $this->record(
            $name,
            $role,
            $node_options
        );
        ddd($response);
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