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
use R3m\Io\Module\File;
use R3m\Io\Module\Sort;

Trait Create {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function create_many($class, $role, $data=[], $options=[]): array
    {
        Core::interactive();
        $name = Controller::name($class);
        $object = $this->object();
        $result = [
            'list' => [],
            'error' => [
                'list' => []
            ]
        ];
        $count = 0;
        $error = 0;
        foreach($data as $record){
            $response = $this->create(
                $class,
                $role,
                $record,
                [
                'is_many' => true,
                'function' => $options['function'] ?? __FUNCTION__,
                'force' => $options['force'] ?? false,
                ]
            );
            if(
                $response &&
                array_key_exists('error', $response)
            ) {
                $result['error']['list'][] = $response['error'];
                $error++;
            }
            elseif(
                $response &&
                array_key_exists('node', $response) &&
                array_key_exists('uuid', $response['node'])
            ) {
                $result['list'][] = $response['node']['uuid'];
                $count++;
            } else {
                $result['error']['list'][] = false;
                $error++;
            }
//            echo 'Count: ' . $count . ' Error: ' . $error . PHP_EOL;
        }
        $result['count'] = $count;
        $result['error']['count'] = $error;
        if($result['error']['count'] === 0){
            unset($result['error']);
        }
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
        if(!empty($result['list'])){
            foreach($result['list'] as $nr => $uuid) {
                $item = [
                    'uuid' => $uuid
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
        return $result;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function create($class, $role, $node=[], $options=[]): false|array
    {
        $function = __FUNCTION__;
        $name = Controller::name($class);
        $object = $this->object();
        $object->request('node', (object) $node);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_meta = $dir_node .
            'Meta'.
            $object->config('ds')
        ;
        $dir_validate = $dir_node .
            'Validate'.
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
        if(
            array_key_exists('function', $options) &&
            $options['function'] === 'import'
        ){
            if(
                is_array($node) &&
                array_key_exists('uuid', $node)
            ){
                $uuid = $node['uuid'];
            }
            elseif(
                is_object($node) &&
                property_exists($node, 'uuid')
            ){
                $uuid = $node->uuid;
            } else {
                $uuid = Core::uuid();
            }
        } else {
            $uuid = Core::uuid();
        }
        $dir_data = $dir_node .
            'Storage' .
            $object->config('ds')
        ;
        $dir_uuid = $dir_data .
            substr($uuid, 0, 2) .
            $object->config('ds')
        ;
        $url = $dir_uuid .
            $uuid .
            $object->config('extension.json')
        ;
        if(File::exist($url)){
            if(
                array_key_exists('force', $options) &&
                $options['force'] === true
            ){
                File::delete($url);
            } else {
                throw new Exception('File exist in create url: ' . $url);
            }
        }
        $dir_binary_search =
            $dir_binary_search_class .
            'Asc' .
            $object->config('ds')
        ;
        $create_dir = $object->data('Create.dir');
        if(empty($create_dir)){
            $this->dir($object,
                [
                    'node' => $dir_node,
                    'uuid' => $dir_uuid,
                    'meta' => $dir_meta,
                    'validate' => $dir_validate,
                    'binary_search_class' => $dir_binary_search_class,
                    'binary_search' => $dir_binary_search,
                ]
            );
            $object->data('Create.dir');
        }

        $object->request('node.uuid', $uuid);
        $validate_url =
            $dir_validate .
            $name .
            $object->config('extension.json');

        $binary_search_url =
            $dir_binary_search .
            'Uuid' .
            $object->config('extension.json');
        $meta_url = $dir_meta . $name . $object->config('extension.json');
        $validate = $this->validate($object, $validate_url,  $name . '.create');
        $response = [];
        if($validate) {
            if($validate->success === true) {
                $expose = $this->expose_get(
                    $object,
                    $name,
                    $name . '.' . __FUNCTION__ . '.expose'
                );
                $node = new Storage();
                $node->data($object->request('node'));
                $node->set('#class', $name);
                if(
                    $expose &&
                    $role
                ) {
                    $record = $this->expose(
                        $node,
                        $expose,
                        $name,
                        __FUNCTION__,
                        $role
                    );
                    if (
                        $record->has('uuid') &&
                        !empty($record->get('uuid'))
                    ) {
                        if(
                            array_key_exists('is_many', $options) &&
                            $options['is_many'] === true
                        ){
                            $record->set('uuid', $uuid);
                            $record->write($url);
                            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                                $command = 'chmod 666 ' . $url;
                                exec($command);
                            }
                            if($object->config(Config::POSIX_ID) === 0){
                                $command = 'chown www-data:www-data ' . $url;
                                exec($command);
                            }
                        } else {
                            $binarySearch = $object->data_read($binary_search_url);
                            if (!$binarySearch) {
                                $binarySearch = new Storage();
                            }
                            $list = $binarySearch->data($name);
                            if (empty($list)) {
                                $list = [];
                            }
                            if (is_object($list)) {
                                $result = [];
                                foreach ($list as $item) {
                                    $result[] = $item;
                                }
                                $list = $result;
                                unset($result);
                            }
                            $record->set('uuid', $uuid);
                            $list[] = (object) [
                                'uuid' => $uuid,
                            ];
                            $list = Sort::list($list)->with([
                                'uuid' => 'ASC',
                            ], [
                                'key_reset' => true,
                            ]);
                            $binarySearch->delete($name);
                            $binarySearch->data($name, $list);
                            $count = 0;
                            foreach ($binarySearch->data($name) as $item) {
                                $item->{'#index'} = $count;
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
                            $meta->set('Sort.' . $name . '.' . $key . '.property', $property);
                            $meta->set('Sort.' . $name . '.' . $key . '.lines', $lines);
                            $meta->set('Sort.' . $name . '.' . $key . '.count', $count);
                            $meta->set('Sort.' . $name . '.' . $key . '.url.asc', $binary_search_url);
                            $meta->write($meta_url);
                            $record->write($url);
                            if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                                $command = 'chmod 666 ' . $url;
                                exec($command);
                                $command = 'chmod 666 ' . $meta_url;
                                exec($command);
                            }
                            if ($object->config(Config::POSIX_ID) === 0) {
                                $command = 'chown www-data:www-data ' . $url;
                                exec($command);
                                $command = 'chown www-data:www-data ' . $meta_url;
                                exec($command);
                            }
                        }
                        $response['node'] = Core::object($record->data(), Core::OBJECT_ARRAY);
                        Event::trigger($object, 'r3m.io.node.data.create', [
                            'class' => $name,
                            'options' => $options,
                            'url' => $url,
                            'binary_search_url' => $binary_search_url,
                            'meta_url' => $meta_url,
                            'node' => $node->data(),
                        ]);
                    } else {
                       throw new Exception('Make sure, you have the right permission (' . $name . '.' . __FUNCTION__ .')');
                    }
                }
            } else {
                $response['error'] = $validate->test;
                Event::trigger($object, 'r3m.io.node.data.create.error', [
                    'class' => $name,
                    'options' => $options,
                    'url' => $url,
                    'binary_search_url' => $binary_search_url,
                    'meta_url' => $meta_url,
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