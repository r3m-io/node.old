<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Sort;

use stdClass;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Sync {

    /**
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
            $role = $this->role_system();
            if(property_exists($options, 'class')){
                if(!in_array($class, $options->class, 1)){
                    continue;
                }
            }
            if(in_array($class, $exception, 1)){

            } else {
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
                if(property_exists($options, 'disable-expose') && $options->{'disable-expose'} == true){

                } else {
                    $expose = $this->expose_get(
                        $object,
                        $class,
                        $class . '.' . __FUNCTION__ . '.expose'
                    );
                }
            }
            $list = [];
            $item = $object->data_read($file->url);
            $time_start = microtime(true);
            $dir_node = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds');
            $dir_binary_tree = $dir_node .
                'BinaryTree' .
                $object->config('ds');
            ;
            $dir_binary_tree_class = $dir_binary_tree .
                $class .
                $object->config('ds')
            ;
            $dir_binary_tree_sort = $dir_binary_tree_class .
                'Asc' .
                $object->config('ds')
            ;

            $url = $dir_binary_tree_sort .
                'Uuid' .
                $object->config('extension.btree');
            if(!File::exist($url)){
                //logger error url not found
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

            $data = File::read($url, File::ARRAY);
            $meta = $object->data_read($meta_url, sha1($meta_url));
            $node_data = new Storage();
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
                    $dir_property_asc_asc = false;
                    $dir_property_asc_desc = false;
                    $url_connect_asc = false;
                    $url_connect_asc_reverse = false;
                    $url_connect_asc_asc = false;
                    $url_connect_asc_asc_reverse = false;
                    $url_connect_asc_desc = false;
                    $url_connect_asc_desc_reverse = false;
                    if(count($properties) > 1){
                        $dir_property_asc = $dir_binary_tree_class .
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
                            $object->config('extension.btree')
                        ;
                        $url_property_asc_desc = $dir_property_asc_desc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.btree')
                        ;
                        $url_connect_asc_asc = $dir_property_asc_asc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.connect')
                        ;
                        $url_connect_asc_asc_reverse = $dir_property_asc_asc .
                            Controller::name(implode('-', array_reverse($properties))) .
                            $object->config('extension.connect')
                        ;
                        $url_connect_asc_desc = $dir_property_asc_desc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.connect')
                        ;
                        $url_connect_asc_desc_reverse = $dir_property_asc_desc .
                            Controller::name(implode('-', array_reverse($properties))) .
                            $object->config('extension.connect')
                        ;
                        $mtime_property = File::mtime($url_property_asc_asc);
                    } else {
                        $dir_property_asc = $dir_binary_tree_class .
                            'Asc' .
                            $object->config('ds')
                        ;
                        $url_property_asc = $dir_property_asc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.btree')
                        ;
                        $url_property_desc = false;

                        $properties_connect = [
                            ...$properties,
                            'uuid'
                        ];
                        $url_connect_asc = $dir_property_asc .
                            Controller::name(implode('-', $properties_connect)) .
                            $object->config('extension.connect')
                        ;
                        $url_connect_asc_reverse = $dir_property_asc .
                            Controller::name(implode('-', array_reverse($properties_connect))) .
                            $object->config('extension.connect')
                        ;
                        $mtime_property = File::mtime($url_property_asc);
                    }
                    $mtime_property = false;
                    if ($mtime === $mtime_property) {
                        //same cache
                        continue;
                    }
                    if (empty($list)) {
                        $list = new Storage();
                        if(is_array($data)){
                            foreach ($data as $index => $uuid) {
                                $uuid = rtrim($uuid, PHP_EOL);
                                $storage_url = $object->config('project.dir.data') .
                                    'Node' .
                                    $object->config('ds') .
                                    'Storage' .
                                    $object->config('ds') .
                                    substr($uuid, 0, 2) .
                                    $object->config('ds') .
                                    $uuid .
                                    $object->config('extension.json')
                                ;
                                $record = $object->data_read($storage_url);
                                if($record === false){
                                    //object no longer exists.
                                    continue;
                                }
                                if($record && $record->has('#class')){
                                    $object_url = $object->config('project.dir.data') .
                                        'Node' .
                                        $object->config('ds') .
                                        'Object' .
                                        $object->config('ds') .
                                        ucfirst($record->get('#class')) .
                                        $object->config('extension.json')
                                    ;

                                    if(
                                        property_exists($options, 'disable-relation') &&
                                        $options->{'disable-relation'} === true
                                    ){
                                        //nothing
                                    } else {
                                        $object_data = $object->data_read($object_url, sha1($object_url));
                                        $relation_options = [
                                            'relation' => true
                                        ];
                                        $record->data($this->binary_tree_relation($record->data(), $object_data, $role, $relation_options));
                                    }

                                }
                                if ($record) {
                                    if(in_array($class, $exception, true)){
                                        $list->set($uuid, $record->data());
                                    }
                                    elseif($expose) {
                                        $record = $this->expose(
                                            $record,
                                            $expose,
                                            $class,
                                            __FUNCTION__,
                                            $role
                                        );
                                        $node = $record->data();
                                        if(is_object($node)){
                                            $node->{'#index'} = $index;
                                        }
                                        $list->set($uuid, $node);
                                    } else {
                                        $node = $record->data();
                                        if(is_object($node)){
                                            $node->{'#index'} = $index;
                                        }
                                        $list->set($uuid, $node);
                                    }
                                }
                            }
                        }
                    }
                    if (array_key_exists(1, $properties)) {
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC',
                            $properties[1] => 'ASC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $index = 0;
                        $binary_tree = [];
                        $connect_property_uuid = [];
                        $connect_uuid_property = []; //ksort at the end
                        foreach ($sort as $key1 => $subList) {
                            foreach($subList as $key2 => $subSubList){
                                foreach ($subSubList as $nr => $node) {
                                    $node_data->data($node);
                                    if(
                                        $node_data->has('uuid') &&
                                        $node_data->has($properties[0]) &&
                                        $node_data->has($properties[1]) &&
                                        $node_data->has('#index')
                                    ){
                                        $binary_tree[$index] = $this->sync_node_data_properties(
                                            $node_data->get($properties[0]),
                                            $node_data->get($properties[1])
                                        );
                                        $connect_property_uuid[$index] = $node_data->get('#index');
                                        $connect_uuid_property[$node_data->get('#index')] = $index;
                                    }
                                    unset($sort[$key1][$key2][$nr]);
                                    unset($subList[$key2][$nr]);
                                    unset($subSubList[$nr]);
                                    $node_data->clear();
                                    $index++;
                                }
                            }
                        }
                        d($url_connect_asc_asc);
                        d($url_connect_asc_asc_reverse);
                        d($connect_property_uuid);
                        d($connect_uuid_property);
                        Dir::create($dir_property_asc_asc, Dir::CHMOD);
                        $connect_asc_asc_lines = File::write($url_connect_asc_asc, implode(PHP_EOL, $connect_property_uuid), 'lines');
                        File::touch($url_connect_asc_asc, $mtime);
                        ksort($connect_uuid_property, SORT_NATURAL);
                        $connect_asc_asc_reverse_lines = File::write($url_connect_asc_asc_reverse, implode(PHP_EOL, $connect_uuid_property), 'lines');
                        File::touch($url_connect_asc_asc_reverse, $mtime);
                        $lines_asc_asc = File::write($url_property_asc_asc, implode(PHP_EOL, $binary_tree), 'lines');
                        File::touch($url_property_asc_asc, $mtime);
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC',
                            $properties[1] => 'DESC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $index = 0;
                        $binary_tree = [];
                        $connect_property_uuid = [];
                        $connect_uuid_property = []; //ksort at the end
                        foreach ($sort as $key1 => $subList) {
                            foreach($subList as $key2 => $subSubList){
                                foreach ($subSubList as $nr => $node) {
                                    $node_data->data($node);
                                    if(
                                        $node_data->has('uuid') &&
                                        $node_data->has($properties[0]) &&
                                        $node_data->has($properties[1]) &&
                                        $node_data->has('#index')
                                    ){
                                        $binary_tree[$index] = $this->sync_node_data_properties(
                                            $node_data->get($properties[0]),
                                            $node_data->get($properties[1])
                                        );
                                        $connect_property_uuid[$index] = $node_data->get('#index');
                                        $connect_uuid_property[$node_data->get('#index')] = $index;
                                    }
                                    unset($sort[$key1][$key2][$nr]);
                                    unset($subList[$key2][$nr]);
                                    unset($subSubList[$nr]);
                                    $node_data->clear();
                                    $index++;
                                }
                            }
                        }
                        d($url_connect_asc_desc);
                        d($url_connect_asc_desc_reverse);
                        Dir::create($dir_property_asc_desc, Dir::CHMOD);
                        $connect_asc_desc_lines = File::write($url_connect_asc_desc, implode(PHP_EOL, $connect_property_uuid), 'lines');
                        File::touch($url_connect_asc_desc, $mtime);
                        ksort($connect_uuid_property, SORT_NATURAL);
                        $connect_asc_desc_reverse_lines = File::write($url_connect_asc_desc_reverse, implode(PHP_EOL, $connect_uuid_property), 'lines');
                        File::touch($url_connect_asc_desc_reverse, $mtime);
                        $lines_asc_desc = File::write($url_property_asc_desc, implode(PHP_EOL, $binary_tree), 'lines');
                        File::touch($url_property_asc_desc, $mtime);
                        if(
                            $connect_asc_asc_lines === $connect_asc_asc_reverse_lines &&
                            $connect_asc_asc_lines === $lines_asc_asc &&
                            $connect_asc_desc_lines === $connect_asc_desc_reverse_lines &&
                            $connect_asc_desc_lines === $lines_asc_desc
                        ) {
                            $count = $index;
                            $sortable = new Storage();
                            $sortable->set('property', $properties);
                            $sortable->set('count', $count);
                            $sortable->set('mtime', $mtime);
                            $sortable->set('lines', $lines_asc_asc);
                            $sortable->set('url.asc.asc', $url_property_asc_asc);
                            $sortable->set('url.asc.desc', $url_property_asc_desc);
                            $sortable->set('property.uuid', $url_connect_asc_asc);
                            $sortable->set('url.connect.asc.asc.property.uuid', $url_connect_asc_asc);
                            $sortable->set('url.connect.asc.asc.uuid.property', $url_connect_asc_asc_reverse);
                            $sortable->set('url.connect.asc.desc.property.uuid', $url_connect_asc_desc);
                            $sortable->set('url.connect.asc.desc.uuid.property', $url_connect_asc_desc_reverse);
                            $key = [
                                'property' => $properties
                            ];
                            $key = sha1(Core::object($key, Core::OBJECT_JSON));
                            $meta->set('Sort.' . $class . '.' . $key, $sortable->data());
                            $meta->write($meta_url);
                        }
                    } else {
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $index = 0;
                        $binary_tree = [];
                        $connect_property_uuid = [];
                        $connect_uuid_property = []; //ksort at the end
                        foreach ($sort as $key => $subList) {
                            foreach ($subList as $nr => $node) {
                                $node_data->data($node);
                                if(
                                    $node_data->has('uuid') &&
                                    $node_data->has($properties[0]) &&
                                    $node_data->has('#index')
                                ){
                                    $binary_tree[$index] = $this->sync_node_data_property(
                                        $node_data->get($properties[0])
                                    );
                                    $connect_property_uuid[$index] = $node_data->get('#index');
                                    $connect_uuid_property[$node_data->get('#index')] = $index;
                                }
                                unset($sort[$key][$nr]);
                                unset($subList[$nr]);
                                $node_data->clear();
                                $index++;
                            }
                        }
                        Dir::create($dir_property_asc, Dir::CHMOD);
                        $connect_asc_lines = File::write($url_connect_asc, implode(PHP_EOL, $connect_property_uuid), 'lines');
                        File::touch($url_connect_asc, $mtime);
                        ksort($connect_uuid_property, SORT_NATURAL);
                        $connect_asc_reverse_lines = File::write($url_connect_asc_reverse, implode(PHP_EOL, $connect_uuid_property), 'lines');
                        File::touch($url_connect_asc_reverse, $mtime);
                        $lines = File::write($url_property_asc, implode(PHP_EOL, $binary_tree), 'lines');
                        File::touch($url_property_asc, $mtime);
                        if(
                            $connect_asc_lines ===
                            $connect_asc_reverse_lines &&
                            $connect_asc_lines === $lines
                        ){
                            $count = $index;
                            $sortable = new Storage();
                            $sortable->set('property', $properties);
                            $sortable->set('count', $count);
                            $sortable->set('mtime', $mtime);
                            $sortable->set('lines', $lines);
                            $sortable->set('url.asc', $url_property_asc);
                            $sortable->set('url.connect.asc.property.uuid', $url_connect_asc);
                            $sortable->set('url.connect.asc.uuid.property', $url_connect_asc_reverse);
                            $key = [
                                'property' => $properties
                            ];
                            $key = sha1(Core::object($key, Core::OBJECT_JSON));
                            $meta->set('Sort.' . $class . '.' . $key, $sortable->data());
                            $meta->write($meta_url);
                        }
                    }
                    $this->sync_file([
                        'meta' => $meta_url,
                        'binary_tree' => $dir_binary_tree,
                        'binary_tree_class' => $dir_binary_tree_class,
                        'binary_tree_sort' => $dir_binary_tree_sort,
                        'property_asc' => $dir_property_asc,
                        'property_asc_asc' => $dir_property_asc_asc,
                        'property_asc_desc' => $dir_property_asc_desc,
                        'url_property_asc' => $url_property_asc,
                        'url_property_asc_asc' => $url_property_asc_asc,
                        'url_property_asc_desc' => $url_property_asc_desc,
                        'url_connect_asc' => $url_connect_asc,
                        'url_connect_asc_reverse' => $url_connect_asc_reverse,
                        'url_connect_asc_asc' => $url_connect_asc_asc,
                        'url_connect_asc_asc_reverse' => $url_connect_asc_asc_reverse,
                        'url_connect_asc_desc' => $url_connect_asc_desc,
                        'url_connect_asc_desc_reverse' => $url_connect_asc_desc_reverse,
                    ]);
                }
            }
            $time_end = microtime(true);
            $time_duration = $time_end - $time_start;
            if($time_duration >= 1){
                echo 'Duration: (3) ' . round($time_duration, 2) . 'sec class: ' . $class . PHP_EOL;
            } else {
                echo 'Duration: (3) ' . round($time_duration * 1000, 2) . 'msec class: ' . $class . PHP_EOL;
            }
        }
    }

    private function sync_node_data_properties($property_1=null, $property_2=null){
        if(is_numeric($property_1)){
            $property_1 = $property_1 + 0;
        }
        if(is_numeric($property_2)){
            $property_2 = $property_2 + 0;
        }
        if(is_string($property_1)){
            $property_1 = '"' . $property_1 . '"';
        }
        if(is_string($property_2)){
            $property_2 = '"' . $property_2 . '"';
        }
        if(is_array($property_1)){
            $property_1 = '"' . implode('", "', $property_1) . '"';
        }
        if(is_array($property_2)){
            $property_2 = '"' . implode('", "', $property_2) . '"';
        }
        if(is_object($property_1)){
            $property_1 = Core::object($property_1, Core::OBJECT_JSON);
        }
        if(is_object($property_2)){
            $property_2 = Core::object($property_2, Core::OBJECT_JSON);
        }
        return '[' . $property_1 . ', ' . $property_2 . ']';
    }

    private function sync_node_data_property($property=null){
        if(is_numeric($property)){
            $property = $property + 0;
        }
        elseif(is_string($property)){
            $property = '"' .$property . '"';
        }
        elseif(is_array($property)){
            $property = '["' . implode('", "', $property) . '"]';
        }
        elseif(is_object($property)){
            $property = '[' . Core::object($property, Core::OBJECT_JSON) . ']';
        }
        return $property;
    }

    private function sync_file($options=[]){
        $object = $this->object();
        if ($object->config(Config::POSIX_ID) === 0) {
            foreach ($options as $key => $value) {
                if (File::exist($value)) {
                    $command = 'chown www-data:www-data ' . $value;
                    exec($command);
                }
            }
        }
        if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
            foreach($options as $key => $value){
                if(Dir::is($value)){
                    $command = 'chmod 777 ' . $value;
                    exec($command);
                }
                elseif(File::is($value)) {
                    $command = 'chmod 666 ' . $value;
                    exec($command);
                }
            }
        }
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    /*
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
            $role = $this->role_system();
            if(property_exists($options, 'class')){
                if(!in_array($class, $options->class, 1)){
                    continue;
                }
            }
            if(in_array($class, $exception, 1)){

            } else {


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
                    if ($mtime === $mtime_property) {
                        //same cache
                        continue;
                    }
                    if (empty($list)) {
                        $list = new Storage();
                        $original = $data->data($class);
                        $storage = [];
                        if(is_array($original) || is_object($original)){
                            foreach ($original as $uuid => $node) {
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
                                    if($record === false){
                                        //object no longer exists.
                                        continue;
                                    }
                                    if($record && $record->has('#class')){
                                        $object_url = $object->config('project.dir.data') .
                                            'Node' .
                                            $object->config('ds') .
                                            'Object' .
                                            $object->config('ds') .
                                            ucfirst($record->get('#class')) .
                                            $object->config('extension.json')
                                        ;
                                        $object_data = $object->data_read($object_url, sha1($object_url));
                                        $relation_options = [
                                            'relation' => true
                                        ];
                                        $record->data($this->relation($record->data(), $object_data, $role, $relation_options));
                                    }
                                    if ($record) {
                                        $storage[] = $node;
                                        if(in_array($class, $exception, true)){
                                            $list->set($uuid, $record->data());
                                        }
                                        elseif($expose) {
                                            $record = $this->expose(
                                                $record,
                                                $expose,
                                                $class,
                                                __FUNCTION__,
                                                $role
                                            );
                                            $list->set($uuid, $record->data());
                                        }
                                    }
                                }
                            }
                        }
                        $object_storage = new Storage();
                        $object_storage->set($class, $storage);
                        $object_storage->write($url);
                    }
                    if (array_key_exists(1, $properties)) {
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
                                        $item = $data->data($class . '.' . $node['uuid']);
//                                        $item = $list->get($node['uuid']);
                                    }
                                    elseif(
                                        is_object($node) &&
                                        property_exists($node, 'uuid')
                                    ){
                                        $item = $data->data($class . '.' . $node->uuid);
//                                        $item = $list->get($node->uuid);
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
                                        $item = $data->data($class . '.' . $node['uuid']);
//                                        $item = $list->get($node['uuid']);
                                    }
                                    elseif(
                                        is_object($node) &&
                                        property_exists($node, 'uuid')
                                    ){
                                        $item = $data->data($class . '.' . $node->uuid);
//                                        $item = $list->get($node->uuid);
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
                                    $item = $data->data($class . '.' . $node['uuid']);
//                                    $item = $list->get($node['uuid']);
                                }
                                elseif(
                                    is_object($node) &&
                                    property_exists($node, 'uuid')
                                ){
                                    $item = $data->data($class . '.' . $node->uuid);
//                                    $item = $list->get($node->uuid);
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
                            if (empty($key) && $key !== 0) {
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
                    $sortable->set('mtime', $mtime);
                    $sortable->set('lines', $lines);
                    if(!empty($url_property_asc_asc)){
                        $sortable->set('url.asc.asc', $url_property_asc_asc);
                        $sortable->set('url.asc.desc', $url_property_asc_desc);
                    } else {
                        $sortable->set('url.asc', $url_property_asc);
                    }
                    $key = [
                        'property' => $properties
                    ];
                    $key = sha1(Core::object($key, Core::OBJECT_JSON));
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
            echo 'Duration: (3) ' . $time_duration . 'ms class: ' . $class . PHP_EOL;
        }
    }
    */
}