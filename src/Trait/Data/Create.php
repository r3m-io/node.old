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
    public function create_many($class='', Storage $data): array
    {
        $name = Controller::name($class);
        $object = $this->object();
        $result = [];
        foreach($data->data($class) as $key => $record){
            $result[$key] = $this->create(
                $class,
                $record,
                [
                'is_many' => true,
                ]
            );
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
        foreach($result as $key => $node) {
            if(is_array($node)){
                if (array_key_exists('error', $node)) {
                    continue;
                }
                if(!array_key_exists('uuid', $node)) {
                    continue;
                }
                $list[] = $node['uuid'];
            }
            elseif(is_object($node)){
                if(property_exists($node, 'error')){
                    continue;
                }
                if(!property_exists($node, 'uuid')){
                    continue;
                }
                $list[] = $node->uuid;
            }
        }
        ddd($list);
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
        $property = [];
        $property[] = 'uuid';
        $key = sha1(Core::object($property, Core::OBJECT_JSON));
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
    public function create($class='', $node=[], $options=[]): false|array
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
        $uuid = Core::uuid();
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
            return false;
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
        $validate = $this->validate($object, $validate_url,  $class . '.create');
        $response = [];
        if($validate) {
            if($validate->success === true) {
                $node = new Storage();
                $node->data($object->request('node'));
                $node->set('#class', $class);
                if(
                    array_key_exists('is_many', $options) &&
                    $options['is_many'] === true
                ){
                    $node->set('url', $url);
                    $node->set('uuid', $uuid);
                    $node->write($url);
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
                    $list = $binarySearch->data($class);
                    if (empty($list)) {
                        $list = [];
                    }
                    if (is_object($list)) {
                        $result = [];
                        foreach ($list as $record) {
                            $result[] = $record;
                        }
                        $list = $result;
                        unset($result);
                    }
                    $node->set('url', $url);
                    $node->set('uuid', $uuid);
                    $list[] = [
                        'uuid' => $uuid,
                    ];
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
                    $property = [];
                    $property[] = 'uuid';
                    $key = sha1(Core::object($property, Core::OBJECT_JSON));
                    $meta->set('Sort.' . $class . '.' . $key . '.property', $property);
                    $meta->set('Sort.' . $class . '.' . $key . '.lines', $lines);
                    $meta->set('Sort.' . $class . '.' . $key . '.count', $count);
                    $meta->set('Sort.' . $class . '.' . $key . '.url.asc', $binary_search_url);
                    $meta->write($meta_url);
                    $node->write($url);
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
                if($object->config(Config::POSIX_ID) === 0){
                    $record = $node->data();
                } else {
                    $expose = $this->expose_get(
                        $object,
                        $class,
                        $class . '.' . $function .'.expose'
                    );
                    ddd($expose);
                    $record = $this->expose(
                        $object,
                        $node->data(),
                        $expose,
                        $class,
                        $function
                    );
                }
                $response['node'] = $record;
                Event::trigger($object, 'r3m.io.node.data.create', [
                    'class' => $class,
                    'options' => $options,
                    'url' => $url,
                    'binary_search_url' => $binary_search_url,
                    'meta_url' => $meta_url,
                    'node' => $node->data(),
                ]);
            } else {
                $response['error'] = $validate->test;
                Event::trigger($object, 'r3m.io.node.data.create.error', [
                    'class' => $class,
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