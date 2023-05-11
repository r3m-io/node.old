<?php

namespace R3m\Io\Node\Trait\Data;

use Exception;
use R3m\Io\App;
use R3m\Io\Config;
use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Sort;
use stdClass;

Trait Sync {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function sync(): void
    {
        $object = $this->object();
        $options = App::options($object);
        if(property_exists($options, 'class')){
            $options->class = explode(',', $options->class);
            foreach($options->class as $nr => $class){
                $options->class[$nr] = Controller::name(trim($class));
            }
        }
        $url_object = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds')
        ;
        $exception = [
            'Role'
        ];
        $dir = new Dir();
        $read = $dir->read($url_object);
        if(empty($read)){
            return;
        }
        foreach ($read as $file) {
            $expose = false;
            $class = File::basename($file->name, $object->config('extension.json'));
            if(property_exists($options, 'class')){
                if(!in_array($class, $options->class, 1)){
                    continue;
                }
            }
            if(in_array($class, $exception, 1)){

            } else {
                $role = $this->role_system();
                /*
                $role = $this->record('Role', $role, [
                    'filter' => [
                        'name' => 'ROLE_SYSTEM'
                    ],
                    'sort' => [
                        'name' => 'ASC'
                    ],
                    'relation' => [
                        'permission:uuid'
                    ]
                ]);
                */
                if(!$role){
                    throw new Exception('Role ROLE_SYSTEM not found');
                }
                $expose = $this->expose_get(
                    $object,
                    $class,
                    $class . '.' . __FUNCTION__ . '.expose'
                );
            }
            $list = [];
            $item = $object->data_read($file->url);

            $time_start = microtime(true);
            $dir_node = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds');
            $dir_binarysearch = $dir_node .
                'BinarySearch' .
                $object->config('ds')
            ;
            $dir_binarysearch_class = $dir_binarysearch .
                $class .
                $object->config('ds');

            $url = $dir_binarysearch_class .
                'Asc' .
                $object->config('ds') .
                'Uuid' .
                $object->config('extension.json');
            if(!File::exist($url)){
                continue;
            }
            $mtime = File::mtime($url);
            $meta_url = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds') .
                'Meta' .
                $object->config('ds') .
                $class .
                $object->config('extension.json');
            $data_raw = $object->data_read($url);
            if (!$data_raw) {
                continue;
            }
            $data = new Storage();
            foreach($data_raw->data($class) as $nr => $raw){
                if(property_exists($raw, 'uuid')){
                    $data->data($class . '.' . $raw->uuid, $raw);
                }
            }
            unset($data_raw);
            $meta = $object->data_read($meta_url, sha1($meta_url));
            if (!$meta) {
                continue;
            }
            if(!$item){
                continue;
            }
            if ($item->has('sort')) {
                foreach ($item->get('sort') as $sort) {
                    $properties = explode(',', $sort);
                    foreach ($properties as $nr => $property) {
                        $properties[$nr] = trim($property);
                    }
                    $url_property_asc = false;
                    $url_property_asc_asc = false;
                    $url_property_asc_desc = false;
                    if(count($properties) > 1){
                        $dir_property_asc = $dir_binarysearch_class .
                            'Asc' .
                            $object->config('ds')
                        ;
                        $dir_property_asc_asc = $dir_property_asc .
                            'Asc' .
                            $object->config('ds')
                        ;
                        $dir_property_asc_desc = $dir_property_asc .
                            'Desc' .
                            $object->config('ds')
                        ;
                        $url_property_asc_asc = $dir_property_asc_asc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.json')
                        ;
                        $url_property_asc_desc = $dir_property_asc_desc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.json')
                        ;
                        $mtime_property = File::mtime($url_property_asc_asc);
                    } else {
                        $dir_property_asc = $dir_binarysearch_class .
                            'Asc' .
                            $object->config('ds')
                        ;
                        $url_property_asc = $dir_property_asc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.json')
                        ;
                        $url_property_desc = false;
                        $mtime_property = File::mtime($url_property_asc);
                    }
                    $mtime_property = false;
                    d($mtime);
                    d($mtime_property);
                    if ($mtime === $mtime_property) {
                        //same cache
                        continue;
                    }
                    if(in_array('role.name', $properties, true)){
                        d($list);
                        ddd('test');
                    }
                    if (empty($list)) {
                        $list = new Storage();
                        foreach ($data->data($class) as $uuid => $node) {

                            if (property_exists($node, 'uuid')) {
                                $storage_url = $object->config('project.dir.data') .
                                    'Node' .
                                    $object->config('ds') .
                                    'Storage' .
                                    $object->config('ds') .
                                    substr($node->uuid, 0, 2) .
                                    $object->config('ds') .
                                    $node->uuid .
                                    $object->config('extension.json');
                                $record = $object->data_read($storage_url);
                                if(in_array('role.name', $properties, true)){
                                    d($storage_url);
                                    ddd($record);
                                }
                                if ($record) {
                                    if(in_array($class, $exception, true)){
                                        $list->set($uuid, $record->data());
                                    }
                                    elseif($expose) {
                                        if(in_array('role.name', $properties, true)){
                                            d($record);
                                            d($expose);
                                        }
                                        $record = $this->expose(
                                            $record,
                                            $expose,
                                            $class,
                                            __FUNCTION__,
                                            $role
                                        );
                                        if(in_array('role.name', $properties, true)){
                                            d($class);
                                            d(__FUNCTION__);
                                        }
                                        $list->set($uuid, $record->data());
                                    }
                                } else {
                                    //event out of sync, send mail
                                }
                            }
                        }
                    }
                    if (array_key_exists(1, $properties)) {
                        if(in_array('role.name', $properties, true)){
                            d($properties);
                            d($list);
                            ddd('test');
                        }

                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC',
                            $properties[1] => 'ASC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $result = new Storage();
                        $index = 0;
                        foreach ($sort as $key1 => $subList) {
                            foreach ($subList as $key2 => $subSubList) {
                                $nodeList = [];
                                foreach ($subSubList as $nr => $node) {
                                    if(
                                        is_array($node) &&
                                        array_key_exists('uuid', $node)
                                    ){
                                        $item = $data->get($class . '.' . $node['uuid']);
                                    }
                                    elseif(
                                        is_object($node) &&
                                        property_exists($node, 'uuid')
                                    ){
                                        $item = $data->get($class . '.' . $node->uuid);
                                    }
                                    if(!$item){
                                        continue;
                                    }
                                    $item->{'#index'} = $index;
                                    $item->{'#sort'} = new stdClass();
                                    $item->{'#sort'}->{$properties[0]} = $key1;
                                    $item->{'#sort'}->{$properties[1]} = $key2;
                                    $nodeList[] = $item;
                                    $index++;
                                }
                                if (empty($key1)) {
                                    $key1 = '""';
                                }
                                if (empty($key2)) {
                                    $key2 = '""';
                                }
                                $result->set($class . '.' . $key1 . '.' . $key2, $nodeList);
                            }
                        }
                        $lines = $result->write($url_property_asc_asc, 'lines');
                        File::touch($url_property_asc_asc, $mtime);
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC',
                            $properties[1] => 'DESC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $result = new Storage();
                        $index = 0;
                        foreach ($sort as $key1 => $subList) {
                            foreach ($subList as $key2 => $subSubList) {
                                $nodeList = [];
                                foreach ($subSubList as $nr => $node) {
                                    if(
                                        is_array($node) &&
                                        array_key_exists('uuid', $node)
                                    ){
                                        $item = $data->get($class . '.' . $node['uuid']);
                                    }
                                    elseif(
                                        is_object($node) &&
                                        property_exists($node, 'uuid')
                                    ){
                                        $item = $data->get($class . '.' . $node->uuid);
                                    }
                                    if(!$item){
                                        continue;
                                    }
                                    $item->{'#index'} = $index;
                                    $item->{'#sort'} = new stdClass();
                                    $item->{'#sort'}->{$properties[0]} = $key1;
                                    $item->{'#sort'}->{$properties[1]} = $key2;
                                    $nodeList[] = $item;
                                    $index++;
                                }
                                if (empty($key1)) {
                                    $key1 = '""';
                                }
                                if (empty($key2)) {
                                    $key2 = '""';
                                }
                                $result->set($class . '.' . $key1 . '.' . $key2, $nodeList);
                            }
                        }
                        $lines_asc_desc = $result->write($url_property_asc_desc, 'lines');
                        File::touch($url_property_asc_desc, $mtime);
                    } else {
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $result = new Storage();
                        $index = 0;
                        foreach ($sort as $key => $subList) {
                            $nodeList = [];
                            foreach ($subList as $nr => $node) {
                                if(
                                    is_array($node) &&
                                    array_key_exists('uuid', $node)
                                ){
                                    $item = $data->get($class . '.' . $node['uuid']);
                                }
                                elseif(
                                    is_object($node) &&
                                    property_exists($node, 'uuid')
                                ){
                                    $item = $data->get($class . '.' . $node->uuid);
                                }
                                if(!$item){
                                    continue;
                                }
                                $item->{'#index'} = $index;
                                $item->{'#sort'} = new stdClass();
                                $item->{'#sort'}->{$properties[0]} = $key;
                                $nodeList[] = $item;
                                $index++;
                            }
                            if (empty($key)) {
                                $key = '""';
                            }
                            $result->set($class . '.' . $key, $nodeList);
                        }
                        $lines = $result->write($url_property_asc, 'lines');
                        File::touch($url_property_asc, $mtime);
                    }
                    $count = $index;
                    $sortable = new Storage();
                    $sortable->set('property', $properties);
                    $sortable->set('count', $count);
                    $sortable->set('lines', $lines);
                    if(!empty($url_property_asc_asc)){
                        $sortable->set('url.asc.asc', $url_property_asc_asc);
                        $sortable->set('url.asc.desc', $url_property_asc_desc);
                    } else {
                        $sortable->set('url.asc', $url_property_asc);
                    }

                    $key = sha1(Core::object($properties, Core::OBJECT_JSON));
                    $meta->set('Sort.' . $class . '.' . $key, $sortable->data());
                    $meta->write($meta_url);
                    if ($object->config(Config::POSIX_ID) === 0) {
                        $command = 'chown www-data:www-data ' . $meta_url;
                        exec($command);
                    }
                    if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                        $command = 'chmod 666 ' . $meta_url;
                        exec($command);
                    }
                    if ($object->config(Config::POSIX_ID) === 0) {
                        if(!empty($url_property_asc_asc)){
                            $command = 'chown www-data:www-data ' . $dir_binarysearch;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_binarysearch_class;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_property_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_property_asc_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_property_asc_desc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $url_property_asc_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $url_property_asc_desc;
                            exec($command);
                        } else {
                            $command = 'chown www-data:www-data ' . $dir_property_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $url_property_asc;
                            exec($command);
                        }
                    }
                    if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                        $command = 'chmod 777 ' . $dir_binarysearch;
                        exec($command);
                        $command = 'chmod 777 ' . $dir_binarysearch_class;
                        exec($command);
                        if(!empty($url_property_asc_asc)){
                            $command = 'chmod 777 ' . $dir_property_asc;
                            exec($command);
                            $command = 'chmod 777 ' . $dir_property_asc_asc;
                            exec($command);
                            $command = 'chmod 777 ' . $dir_property_asc_desc;
                            exec($command);
                            $command = 'chmod 666 ' . $url_property_asc_asc;
                            exec($command);
                            $command = 'chmod 666 ' . $url_property_asc_desc;
                            exec($command);
                        } else {
                            $command = 'chmod 777 ' . $dir_property_asc;
                            exec($command);
                            $command = 'chmod 666 ' . $url_property_asc;
                            exec($command);
                        }
                    }
                }
            }
            $time_end = microtime(true);
            $time_duration = round(($time_end - $time_start) * 1000, 2);
            echo 'Duration: ' . $time_duration . 'ms' . PHP_EOL;
        }
    }
}