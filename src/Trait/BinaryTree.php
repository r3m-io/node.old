<?php

namespace R3m\Io\Node\Trait;

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
use SplFileObject;
use stdClass;

Trait BinaryTree {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    private function binary_tree_list_create($class, $role, $options=[]): void
    {
        $object = $this->object();
        $name = Controller::name($class);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds');
        $dir_binary_tree = $dir_node .
            'BinaryTree' .
            $object->config('ds') .
            $name .
            $object->config('ds')
        ;
        $url = $dir_binary_tree .
            'Asc' .
            $object->config('ds') .
            'Uuid' .
            $object->config('extension.btree')
        ;
        d($url);
        d(File::exist($url));
        if(!File::exist($url)){
            return;
        }
        $url_uuid = $url;
        $meta_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $meta = $object->data_read($meta_url, sha1($meta_url));
        if(!$meta){
            return;
        }
        $object_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $object_class = $object->data_read($object_url);
        $list = new Storage();
        $mtime = File::mtime($url);
        $properties = [];
        $url_key = 'url.';
        $url_connect_key = '';
        if(!array_key_exists('sort', $options)){
            $debug = debug_backtrace(true);
            ddd($debug[0]['file'] . ' ' . $debug[0]['line']);
        }
        foreach($options['sort'] as $key => $order) {
            if(empty($properties)){
                $url_key .= 'asc.';
                $url_connect_key .= 'Asc' . $object->config('ds');
            } else {
                $url_key .= strtolower($order) . '.';
                $url_connect_key .= ucfirst(strtolower($order)) . $object->config('ds');
            }
            $properties[] = $key;
        }
        $property = implode('-', $properties);
        d($property);
        $url_connect_property = $dir_binary_tree .
            $url_connect_key .
            Controller::name($property) .
            $object->config('extension.connect')
        ;
        $url_key = substr($url_key, 0, -1);
        $sort_key = [
            'property' => $properties,
        ];
        $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
        $url_property = $meta->get('Sort.' . $class . '.' . $sort_key . '.'. $url_key);
        if(empty($url_property)){
            throw new Exception('Binary search list not found in meta file (class: ' . $class . '). properties: ['. implode(', ', $properties) . '] sort key: ' . $sort_key . ' url key: ' . $url_key);
        }
        $sort_lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
        if(!empty($options['filter'])){
            $key = [
                'filter' => $options['filter'],
                'sort' => $options['sort'],
                'page' => $options['page'] ?? 1,
                'limit' => $options['limit'] ?? 1000,
                'mtime' => $mtime,
            ];
            $key = sha1(Core::object($key, Core::OBJECT_JSON));
            $file = new SplFileObject($url_property);
            if(File::exist($url_uuid)){
                $file_uuid = new SplFileObject($url_uuid);
            } else {
                $file_uuid = null;
            }
            if(File::exist($url_connect_property)){
                $file_connect_property = new SplFileObject($url_connect_property);
            } else {
                $file_connect_property = null;
            }
            $limit = $meta->get('Filter.' . $name . '.' . $key . '.limit') ?? 1000;
            $filter_list = $this->binary_tree_page(
                $file,
                $file_uuid,
                $file_connect_property,
                $role,
                $counter,
                [
                    'filter' => $options['filter'],
                    'page' => $options['page'] ?? 1,
                    'limit' => $options['limit'] ?? 1000,
                    'lines'=> $sort_lines,
                    'counter' => 0,
                    'direction' => 'next',
                    'url' => $url_property,
                    'url_uuid' => $url_uuid,
                    'url_connect_property' => $url_connect_property,
                    'function' => $options['function'],
                    'relation' => $options['relation'],
                    'name' => $name,
                    'ramdisk' => $options['ramdisk'] ?? false,
                    'mtime' => $mtime
                ]
            );
            /*
            $filter_list = $this->binary_tree_list($file, [
                'filter' => $options['filter'],
                'limit' => $limit,
                'lines'=> $sort_lines,
                'counter' => 0,
                'direction' => 'next',
                'url' => $url_property
            ]);
            */
            if(!empty($filter_list)){
                $filter = [];
                $index = false;
                foreach($filter_list as $index => $node){
                    if(property_exists($node, 'uuid')){
                        $filter[$key][$index] = [
                            'uuid' => $node->uuid,
                            '#index' => $index
                        ];
                    }
                }
                $filter_dir = $dir_node .
                    'Filter' .
                    $object->config('ds')
                ;
                $filter_name_dir = $filter_dir .
                    $name .
                    $object->config('ds')
                ;
                Dir::create($filter_name_dir, Dir::CHMOD);
                $filter_url = $filter_name_dir .
                    $key .
                    $object->config('extension.json')
                ;
                $storage = new Storage($filter);
                $lines = $storage->write($filter_url, 'lines');
                File::touch($filter_url, $mtime);
                if($index === false){
                    $count = 0;
                } else {
                    $count = $index + 1;
                }
                $meta->set('Filter.' . $name . '.' . $key . '.lines', $lines);
                $meta->set('Filter.' . $name . '.' . $key . '.count', $count);
                $meta->set('Filter.' . $name . '.' . $key . '.limit', $limit);
                $meta->set('Filter.' . $name . '.' . $key . '.mtime', $mtime);
                $meta->set('Filter.' . $name . '.' . $key . '.filter', $options['filter']);
                $meta->set('Filter.' . $name . '.' . $key . '.sort', $options['sort']);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $filter_url;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $filter_dir;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $filter_name_dir;
                    exec($command);
                }
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                    $command = 'chmod 666 ' . $filter_url;
                    exec($command);
                    $command = 'chmod 777 ' . $filter_dir;
                    exec($command);
                    $command = 'chmod 777 ' . $filter_name_dir;
                    exec($command);
                }
            }
        }
        elseif(!empty($options['where'])){
            $options['where'] = $this->where_convert($options['where']);
            $key = [
                'where' => $options['where'],
                'sort' => $options['sort'],
                'page' => $options['page'] ?? 1,
                'limit' => $options['limit'] ?? 1000,
                'mtime' => $mtime,
            ];
            $key = sha1(Core::object($key, Core::OBJECT_JSON));
            $file = new SplFileObject($url_property);
            $file_uuid = new splFileObject($url_uuid);
            $file_connect_property =new splFileObject($url_connect_property);
            $limit = $meta->get('Where.' . $name . '.' . $key . '.limit') ?? 1000;
            $where_list = $this->binary_tree_page(
                $file,
                $file_uuid,
                $file_connect_property,
                $role,
                $counter,
                [
                    'where' => $options['where'],
                    'page' => $options['page'] ?? 1,
                    'limit' => $options['limit'] ?? 1000,
                    'lines'=> $sort_lines,
                    'counter' => 0,
                    'direction' => 'next',
                    'url' => $url_property,
                    'url_uuid' => $url_uuid,
                    'url_connect_property' => $url_connect_property,
                    'function' => $options['function'],
                    'relation' => $options['relation'],
                    'name' => $name,
                    'ramdisk' => $options['ramdisk'] ?? false,
                    'mtime' => $mtime
                ]
            );
            /*
            $where_list = $this->binary_tree_list($file, [
                'where' => $options['where'],
                'limit' => $limit,
                'lines'=> $sort_lines,
                'counter' => 0,
                'direction' => 'next',
                'url' => $url_property,
            ]);
            */
            if(!empty($where_list)){
                $where = [];
                $index = false;
                foreach($where_list as $index => $node){
                    $where[$key][$index] = [
                        'uuid' => $node->uuid,
                        '#index' => $index,
//                        '#key' => $key
                    ];
                }
                $where_dir = $dir_node .
                    'Where' .
                    $object->config('ds')
                ;
                $where_name_dir = $where_dir .
                    $name .
                    $object->config('ds')
                ;
                Dir::create($where_name_dir, Dir::CHMOD);
                $where_url = $where_name_dir .
                    $key .
                    $object->config('extension.json')
                ;
                $storage = new Storage($where);
                $lines = $storage->write($where_url, 'lines');
                File::touch($where_url, $mtime);
                if($index === false){
                    $count = 0;
                } else {
                    $count = $index + 1;
                }
                $meta->set('Where.' . $name . '.' . $key . '.lines', $lines);
                $meta->set('Where.' . $name . '.' . $key . '.count', $count);
                $meta->set('Where.' . $name . '.' . $key . '.limit', $limit);
                $meta->set('Where.' . $name . '.' . $key . '.mtime', $mtime);
                $meta->set('Where.' . $name . '.' . $key . '.where', $options['where']);
                $meta->set('Where.' . $name . '.' . $key . '.sort', $options['sort']);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $where_url;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $where_dir;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $where_name_dir;
                    exec($command);
                }
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                    $command = 'chmod 666 ' . $where_url;
                    exec($command);
                    $command = 'chmod 777 ' . $where_dir;
                    exec($command);
                    $command = 'chmod 777 ' . $where_name_dir;
                    exec($command);
                }
            }
        }
        $meta->write($meta_url);
        if($object->config(Config::POSIX_ID) === 0){
            $command = 'chown www-data:www-data ' . $meta_url;
            exec($command);
        }
        if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
            $command = 'chmod 666 ' . $meta_url;
            exec($command);
        }
    }

    private function binary_tree_relation_inner($relation, $data=[], $options=[], &$counter=0): false|array|stdClass
    {
        $object = $this->object();
        $counter++;
        if($counter > 1024){
            $is_loaded = $object->data('R3m.Io.Node.BinaryTree.relation');
            d($is_loaded);
            d($relation);
            ddd($data);
        }
        if(!property_exists($relation, 'type')){
            return false;
        }
        $is_allowed = false;
        $options_relation = $options['relation'] ?? [];
        if(is_bool($options_relation) && $options_relation === true){
            $is_allowed = true;
        }
        elseif(is_bool($options_relation) && $options_relation === false){
            $is_allowed = false;
        }
        elseif(is_array($options_relation)){
            foreach($options_relation as $option){
                if(strtolower($option) === strtolower($relation->class)){
                    $is_allowed = true;
                    break;
                }
            }
        }
        switch($relation->type){
            case 'one-many':
                if(!is_array($data)){
                    return false;
                }
                foreach($data as $relation_data_nr => $relation_data_uuid){
                    if(
                        $is_allowed &&
                        is_string($relation_data_uuid)
                    ){
                        $relation_data_url = $object->config('project.dir.data') .
                            'Node' .
                            $object->config('ds') .
                            'Storage' .
                            $object->config('ds') .
                            substr($relation_data_uuid, 0, 2) .
                            $object->config('ds') .
                            $relation_data_uuid .
                            $object->config('extension.json')
                        ;
                        $relation_data = $object->data_read($relation_data_url, sha1($relation_data_url));
                        if($relation_data){
//                            $record = $relation_data->data();

                            $relation_object_url = $object->config('project.dir.data') .
                                'Node' .
                                $object->config('ds') .
                                'Object' .
                                $object->config('ds') .
                                $relation->class .
                                $object->config('extension.json')
                            ;
                            $relation_object_data = $object->data_read($relation_object_url, sha1($relation_object_url));
                            $relation_object_relation = $relation_object_data->data('relation');

                            $is_loaded = $object->data('R3m.Io.Node.BinaryTree.relation');
                            if(empty($is_loaded)){
                                $is_loaded = [];
                            }
                            if($relation_data->has('#class')){
                                $is_loaded[] = $relation_data->get('#class');
                                $object->data('R3m.Io.Node.BinaryTree.relation', $is_loaded);
                            }
                            if(is_array($relation_object_relation)){
                                foreach($relation_object_relation as $relation_object_relation_nr => $relation_object_relation_data){
                                    if(
                                        property_exists($relation_object_relation_data, 'class') &&
                                        property_exists($relation_object_relation_data, 'attribute')
                                    ){
                                        if(
                                            in_array(
                                                $relation_object_relation_data->class,
                                                $is_loaded,
                                                true
                                            )
                                        ){
                                            //already loaded
                                            continue;
                                        }
                                    }
                                    $selected = $relation_data->get($relation_object_relation_data->attribute);
                                    $selected = $this->binary_tree_relation_inner($relation_object_relation_data, $selected, $options, $counter);
                                    $relation_data->set($relation_object_relation_data->attribute, $selected);
                                }
                            }
                            $data[$relation_data_nr] = $relation_data->data();
                        } else {
                            //old data, remove from list
                            unset($data[$relation_data_nr]);
                        }
                    }
                }
            break;
            case 'many-one':
                if(
                    $is_allowed &&
                    is_string($data)
                ){
                    $relation_data_url = $object->config('project.dir.data') .
                        'Node' .
                        $object->config('ds') .
                        'Storage' .
                        $object->config('ds') .
                        substr($data, 0, 2) .
                        $object->config('ds') .
                        $data .
                        $object->config('extension.json')
                    ;
                    $relation_data = $object->data_read($relation_data_url, sha1($relation_data_url));
                    if($relation_data) {
//                        $record = $relation_data->data();

                        $relation_object_url = $object->config('project.dir.data') .
                            'Node' .
                            $object->config('ds') .
                            'Object' .
                            $object->config('ds') .
                            $relation->class .
                            $object->config('extension.json')
                        ;
                        $relation_object_data = $object->data_read($relation_object_url, sha1($relation_object_url));
                        $relation_object_relation = $relation_object_data->data('relation');

                        $is_loaded = $object->data('R3m.Io.Node.BinaryTree.relation');
                        if(empty($is_loaded)){
                            $is_loaded = [];
                        }
                        if($relation_data->has('#class')){
                            $is_loaded[] = $relation_data->get('#class');
                            $object->data('R3m.Io.Node.BinaryTree.relation', $is_loaded);
                        }
                        if(is_array($relation_object_relation)){
                            foreach($relation_object_relation as $relation_object_relation_nr => $relation_object_relation_data){
                                if(
                                    property_exists($relation_object_relation_data, 'class') &&
                                    property_exists($relation_object_relation_data, 'attribute')
                                ){

                                    if(
                                        in_array(
                                            $relation_object_relation_data->class,
                                            $is_loaded,
                                            true
                                        )
                                    ){
                                        //already loaded
                                        continue;
                                    }
                                }
                                $selected = $relation_data->get($relation_object_relation_data->attribute);
                                $selected = $this->binary_tree_relation_inner($relation_object_relation_data, $selected, $options, $counter);
                                $relation_data->set($relation_object_relation_data->attribute, $selected);
                            }
                        }
                        $data = $relation_data->data();
                    }
                }
            break;
            case 'one-one':
                ddd($relation);
            break;
        }
        return $data;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    private function binary_tree_relation($record, $data, $role, $options=[]){
        $object = $this->object();
        if(!$role){
            return $record;
        }
        if($data){
            $node = new Storage($record);
            $relations = $data->data('relation');
            if(!$relations){
                return $record;
            }
            if(
                array_key_exists('relation', $options) &&
                is_bool($options['relation']) &&
                $options['relation'] === false
            ){
                return $record;
            }
            if(!is_array($relations)){
                return $record;
            }
            foreach($relations as $relation){
                if(
                    property_exists($relation, 'type') &&
                    property_exists($relation, 'class') &&
                    property_exists($relation, 'attribute')
                ){
                    $is_allowed = false;
                    $options_relation = $options['relation'] ?? [];
                    if(is_bool($options_relation) && $options_relation === true){
                        $is_allowed = true;
                    }
                    elseif(is_bool($options_relation) && $options_relation === false){
                        $is_allowed = false;
                    }
                    elseif(is_array($options_relation)){
                        foreach($options_relation as $option){
                            if(strtolower($option) === strtolower($relation->class)){
                                $is_allowed = true;
                                break;
                            }
                        }
                    }
                    switch(strtolower($relation->type)){
                        case 'one-one':
                            if(
                                $is_allowed &&
                                $node->has($relation->attribute)
                            ){
                                $uuid = $node->get($relation->attribute);
                                if(!is_string($uuid)){
                                    break;
                                }
                                $relation_url = $object->config('project.dir.data') .
                                    'Node' .
                                    $object->config('ds') .
                                    'Storage' .
                                    $object->config('ds') .
                                    substr($uuid, 0, 2) .
                                    $object->config('ds') .
                                    $uuid .
                                    $object->config('extension.json')
                                ;
                                $relation_data = $object->data_read($relation_url, sha1($relation_url));
                                if($relation_data) {
                                    $relation_object_url = $object->config('project.dir.data') .
                                        'Node' .
                                        $object->config('ds') .
                                        'Object' .
                                        $object->config('ds') .
                                        $relation_data->get('#class') .
                                        $object->config('extension.json');
                                    $relation_object_data = $object->data_read($relation_object_url, sha1($relation_object_url));
                                    if (
                                        $relation_object_data &&
                                        $relation_object_data->has('relation')
                                    ) {
                                        $relation_object_relation = $relation_object_data->get('relation');
                                        if (is_array($relation_object_relation)) {
                                            foreach ($relation_object_relation as $relation_nr => $relation_relation) {
                                                if (
                                                    property_exists($relation_relation, 'type') &&
                                                    property_exists($relation_relation, 'class') &&
                                                    property_exists($record, '#class') &&
                                                    $relation_relation->type === 'many-one' &&
                                                    $relation_relation->class === $record->{'#class'}
                                                ) {
                                                    //don't need cross-reference, parent is this.
                                                    continue;
                                                }
                                                if (
                                                    property_exists($relation_relation, 'type') &&
                                                    property_exists($relation_relation, 'class') &&
                                                    property_exists($record, '#class') &&
                                                    $relation_relation->type === 'one-one' &&
                                                    $relation_relation->class === $record->{'#class'}
                                                ) {
                                                    //don't need cross-reference, parent is this.
                                                    continue;
                                                }
                                                if (
                                                    property_exists($relation_relation, 'attribute')
                                                ) {
                                                    $relation_data_data = $relation_data->get($relation_relation->attribute);
                                                    $relation_data_data = $this->binary_tree_relation_inner($relation_relation, $relation_data_data, $options);
                                                    $relation_data->set($relation_relation->attribute, $relation_data_data);
                                                }
                                            }
                                        }
                                        if ($relation_data) {
                                            $node->set($relation->attribute, $relation_data->data());
                                        }
                                    } else {
                                        if ($relation_data) {
                                            $node->set($relation->attribute, $relation_data->data());
                                        }
                                    }
                                }
                            }
                            $record = $node->data();
                            break;
                        case 'one-many':
                            if(
                                $is_allowed &&
                                $node->has($relation->attribute)
                            ){
                                $one_many = $node->get($relation->attribute);
                                if(!is_array($one_many)){
                                    break;
                                }
                                foreach($one_many as $nr => $uuid){
                                    if(!is_string($uuid)){
                                        continue;
                                    }
                                    $relation_url = $object->config('project.dir.data') .
                                        'Node' .
                                        $object->config('ds') .
                                        'Storage' .
                                        $object->config('ds') .
                                        substr($uuid, 0, 2) .
                                        $object->config('ds') .
                                        $uuid .
                                        $object->config('extension.json')
                                    ;
                                    $relation_data = $object->data_read($relation_url, sha1($relation_url));
                                    if($relation_data){
                                        if(
                                            $relation_data->has('#class')
                                        ){
                                            $relation_object_url = $object->config('project.dir.data') .
                                                'Node' .
                                                $object->config('ds') .
                                                'Object' .
                                                $object->config('ds') .
                                                $relation_data->get('#class') .
                                                $object->config('extension.json')
                                            ;
                                            $relation_object_data = $object->data_read($relation_object_url, sha1($relation_object_url));
                                            if(
                                                $relation_object_data &&
                                                $relation_object_data->has('relation')
                                            ){
                                                $relation_object_relation = $relation_object_data->get('relation');
                                                if(is_array($relation_object_relation)){
                                                    foreach($relation_object_relation as $relation_nr => $relation_relation){
                                                        if(
                                                            property_exists($relation_relation, 'type') &&
                                                            property_exists($relation_relation, 'class') &&
                                                            property_exists($record, '#class') &&
                                                            $relation_relation->type === 'many-one' &&
                                                            $relation_relation->class === $record->{'#class'}
                                                        ){
                                                            //don't need cross-reference, parent is this.
                                                            continue;
                                                        }
                                                        if(
                                                            property_exists($relation_relation, 'type') &&
                                                            property_exists($relation_relation, 'class') &&
                                                            property_exists($record, '#class') &&
                                                            $relation_relation->type === 'one-one' &&
                                                            $relation_relation->class === $record->{'#class'}
                                                        ){
                                                            //don't need cross-reference, parent is this.
                                                            continue;
                                                        }
                                                        if(
                                                            property_exists($relation_relation, 'attribute')
                                                        ){
                                                            $relation_data_data = $relation_data->get($relation_relation->attribute);
                                                            $relation_data_data = $this->binary_tree_relation_inner($relation_relation, $relation_data_data, $options);
                                                            $relation_data->set($relation_relation->attribute, $relation_data_data);
                                                        }
                                                    }
                                                }
                                                if($relation_data){
                                                    $one_many[$nr] = $relation_data->data();
                                                }
                                            } else {
                                                if($relation_data){
                                                    $one_many[$nr] = $relation_data->data();
                                                }
                                            }
                                        }
                                    }
                                }
                                $node->set($relation->attribute, $one_many);
                            }
                            $record = $node->data();
                            break;
                        case 'many-one':
                            if(
                                $is_allowed &&
                                $node->has($relation->attribute)
                                //add is_uuid
                            ){
                                $uuid = $node->get($relation->attribute);
                                if(!is_string($uuid)){
                                    break;
                                }
                                $relation_url = $object->config('project.dir.data') .
                                    'Node' .
                                    $object->config('ds') .
                                    'Storage' .
                                    $object->config('ds') .
                                    substr($uuid, 0, 2) .
                                    $object->config('ds') .
                                    $uuid .
                                    $object->config('extension.json')
                                ;
                                $relation_data = $object->data_read($relation_url, sha1($relation_url));
                                if($relation_data){
                                    if(
                                        $relation_data->has('#class')
                                    ) {
                                        $relation_object_url = $object->config('project.dir.data') .
                                            'Node' .
                                            $object->config('ds') .
                                            'Object' .
                                            $object->config('ds') .
                                            $relation_data->get('#class') .
                                            $object->config('extension.json')
                                        ;
                                        $relation_object_data = $object->data_read($relation_object_url, sha1($relation_object_url));
                                        if($relation_object_data){
                                            foreach($relation_object_data->get('relation') as $relation_nr => $relation_relation){
                                                if(
                                                    property_exists($relation_relation, 'type') &&
                                                    property_exists($relation_relation, 'class') &&
                                                    property_exists($record, '#class') &&
                                                    $relation_relation->type === 'many-one' &&
                                                    $relation_relation->class === $record->{'#class'}
                                                ){
                                                    //don't need cross-reference, parent is this.
                                                    continue;
                                                }
                                                elseif(
                                                    property_exists($relation_relation, 'type') &&
                                                    property_exists($relation_relation, 'class') &&
                                                    property_exists($record, '#class') &&
                                                    $relation_relation->type === 'one-one' &&
                                                    $relation_relation->class === $record->{'#class'}
                                                ){
                                                    //don't need cross-reference, parent is this.
                                                    continue;
                                                }
                                                elseif(
                                                    property_exists($relation_relation, 'type') &&
                                                    property_exists($relation_relation, 'class') &&
                                                    property_exists($record, '#class') &&
                                                    $relation_relation->type === 'one-many' &&
                                                    $relation_relation->class === $record->{'#class'}
                                                ){
                                                    //don't need cross-reference, parent is this.
                                                    continue;
                                                }
                                                if(
                                                    property_exists($relation_relation, 'attribute')
                                                ){
                                                    $relation_data_data = $relation_data->get($relation_relation->attribute);
                                                    $relation_data_data = $this->binary_tree_relation_inner($relation_relation, $relation_data_data, $options);
                                                    $relation_data->set($relation_relation->attribute, $relation_data_data);
                                                }
                                            }
                                        }
                                        if($relation_data){
                                            $node->set($relation->attribute, $relation_data->data());
                                        }
                                    }
                                }
                            }
                            $record = $node->data();
                            break;
                    }
                }
            }
        }
        return $record;
    }

    /**
     * @throws Exception
     */
    private function binary_tree_page($file, $file_uuid, $file_connect_property, $role, &$counter=0, $options=[]): array
    {
        $object = $this->object();
        $index = 0;
        if(
            array_key_exists('page', $options) &&
            array_key_exists('limit', $options)
        ){
            $index = ($options['page'] * $options['limit']) - $options['limit'];
        }
        $time_start = microtime(true);
        $url = false;
        if(
            array_key_exists('mtime', $options) &&
            array_key_exists('ramdisk', $options) &&
            $options['ramdisk'] === true
        ){
            $ramdisk_options = $options;
            unset($ramdisk_options['mtime']);
            $key = sha1(Core::object($ramdisk_options, Core::OBJECT_JSON));
            $url = $object->config('ramdisk.url') .
                $object->config(Config::POSIX_ID) .
                $object->config('ds') .
                'Package' .
                $object->config('ds') .
                'R3m-Io' .
                $object->config('ds') .
                'Node' .
                $object->config('ds') .
                'Binary' .
                '.' .
                'Page' .
                '-' .
                $key .
                $object->config('extension.json')
            ;
            //echo 'binarysearch:ramdisk:url: ' . $url . PHP_EOL;
            if(
                File::exist($url) &&
                File::mtime($url) === $options['mtime']
            ){
                $data = $object->data_read($url, $key);
                if($data){
                    if($object->config('project.log.node')){
                        $time_end = microtime(true);
                        $duration = $time_end - $time_start;
                        if($duration < 1) {
                            $object->logger($object->config('project.log.node'))->info('Duration: (8) ' . round($duration * 1000, 2) . ' msec url: ' . $options['url']);
                        } else {
                            $object->logger($object->config('project.log.node'))->info('Duration: (9) ' . round($duration, 2) . ' sec url: ' . $options['url']);
                        }
                    }
                    $data->set('output.key', $key);
                    $result = (array) $data->data('page');
                    $counter = $data->data('output.count');
                    d($result);
                    return $result;
                }
            }
        }
        $start = $index;
        $end = $start + $options['limit'];
        $page = [];
        $record_index = $index;
        d($start);
        d($end);
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_tree_index($file, $file_uuid, $file_connect_property, [
//                'page' => $options['page'],
//                'limit' => $options['limit'],
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
                'url' => $options['url'],
                'url_uuid' => $options['url_uuid'],
                'url_connect_property' => $options['url_connect_property'],
            ]);
            d($i);
            d($options);
            d($record);
            if(
                $record
            ){
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                d($record);
                if(!property_exists($record, '#class')){
                    $end++;
                    //need to trigger sync
                    //delete file ?
                    continue;
                }
                $object_url = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Object' .
                    $object->config('ds') .
                    $options['name'] ?? ucfirst($record->{'#class'}) .
                    $object->config('extension.json')
                ;
                $options_json = Core::object($options, Core::OBJECT_JSON);
                $object_data = $object->data_read($object_url, sha1($object_url . '.' . $options_json));
                /*
                $is_loaded = [];
                if(property_exists($record, '#class')){
                    $is_loaded[] = $record->{'#class'};
                    $object->data('R3m.Io.Node.BinarySearch.relation', $is_loaded);
                }
                */
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
                    $options['function'],
                    $role
                );
                $record = $record->data();
                //need object file, so need $class
                //load relations so we can filter / where on them
                if(
                    !empty($record) &&
                    !empty($options['filter'])
                ){
                    $record = $this->filter($record, $options['filter'], $options);
                }
                elseif(
                    !empty($record) &&
                    !empty($options['where'])
                ){
                    $record = $this->where($record, $options['where'], $options);
                }
                if($record){
                    $record->{'#index'} = $record_index;
                    $page[] = $record;
                    $record_index++;
                    $counter++;
                } else {
                    $end++;
                }
            } else {
                break;
            }
        }
        d($url);
        d($page);
        if(
            array_key_exists('mtime', $options) &&
            array_key_exists('ramdisk', $options) &&
            $options['ramdisk'] === true &&
            $url
        ){
            $cache = new Storage();
            $cache->set('page', $page);
            $cache->set('input', $options);
            $cache->set('output.count', $counter);
            $cache->set('output.page', $options['page']);
            $cache->set('output.limit', $options['limit']);
            $cache->write($url);
            File::touch($url, $options['mtime']);
            File::touch($options['url'], $options['mtime']);
        }
        if($object->config('project.log.node')){
            $time_end = microtime(true);
            $duration = $time_end - $time_start;
            if($duration < 1) {
                $object->logger($object->config('project.log.node'))->info('Duration: (1) ' . round($duration * 1000, 2) . ' msec url: ' . $options['url']);
            } else {
                $object->logger($object->config('project.log.node'))->info('Duration: (2) ' . round($duration, 2) . ' sec url: ' . $options['url']);
            }
        }
        return $page;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    private function binary_tree_count($file, $role, $options=[]): int
    {
        $time_start = microtime(true);
        $object = $this->object();
        $count = 0;
        if(
            array_key_exists('url_uuid', $options) &&
            File::exist($options['url_uuid'])
        ){
            $read = File::read($options['url_uuid'], File::ARRAY);
            foreach($read as $uuid){
                $uuid = rtrim($uuid, PHP_EOL);
                $url = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Storage' .
                    $object->config('ds') .
                    substr($uuid, 0, 2) .
                    $object->config('ds') .
                    $uuid .
                    $object->config('extension.json')
                ;
                $read = $object->data_read($url);
                $record = $read->data();
                if(
                    is_object($record) &&
                    property_exists($record, '#class')
                ){
                    $class = $record->{'#class'};
                    $object_url = $object->config('project.dir.data') .
                        'Node' .
                        $object->config('ds') .
                        'Object' .
                        $object->config('ds') .
                        ucfirst($class) .
                        $object->config('extension.json')
                    ;
                    $options_json = Core::object($options, Core::OBJECT_JSON);
                    $object_data = $object->data_read($object_url, sha1($object_url . '.' . $options_json));
                    $record = $this->binary_tree_relation($record, $object_data, $role, $options);
                    $expose = $this->expose_get(
                        $object,
                        $class,
                        $class . '.' . $options['function'] . '.expose'
                    );
                    $node = new Storage($record);
                    $record = $this->expose(
                        $node,
                        $expose,
                        $class,
                        $options['function'],
                        $role
                    );
                    $record = $record->data();
                    if(!empty($options['filter'])){
                        $record = $this->filter($record, $options['filter'], $options);
                    }
                    elseif(!empty($options['where'])){
                        $record = $this->where($record, $options['where'], $options);
                    }
                    if($record){
                        $count++;
                    }
                }
            }
        }
        if($object->config('project.log.node')){
            $time_end = microtime(true);
            $duration = $time_end - $time_start;
            if($duration < 1) {
                $object->logger($object->config('project.log.node'))->info('Duration: (4) ' . round($duration * 1000, 2) . ' msec url: ' . $options['url'], [ $count ] );
            } else {
                $object->logger($object->config('project.log.node'))->info('Duration: (5) ' . round($duration, 2) . ' sec url: ' . $options['url'], [ $count ]);
            }
        }
        return $count;
    }

    /**
     * @throws Exception
     */
    /*
    private function binary_tree_list($file, $options=[]): array
    {
        if(!array_key_exists('limit', $options)){
            return [];
        }
        if(!array_key_exists('lines', $options)){
            return [];
        }
        $time_start = microtime(true);
        $object = $this->object();
        $url = false;
        if(
            array_key_exists('mtime', $options) &&
            array_key_exists('ramdisk', $options) &&
            $options['ramdisk'] === true
        ){
            $ramdisk_options = $options;
            unset($ramdisk_options['mtime']);
            $key = sha1(Core::object($ramdisk_options, Core::OBJECT_JSON));
            $url = $object->config('ramdisk.url') .
                $object->config(Config::POSIX_ID) .
                $object->config('ds') .
                'Package' .
                $object->config('ds') .
                'R3m-Io' .
                $object->config('ds') .
                'Node' .
                $object->config('ds') .
                'Binary' .
                '.' .
                'Page' .
                '-' .
                $key .
                $object->config('extension.json')
            ;
            if(
                File::exist($url) &&
                File::mtime($url) === $options['mtime']
            ){
                $data = $object->data_read($url, $key);
                if($data){
                    if($object->config('project.log.node')){
                        $time_end = microtime(true);
                        $duration = $time_end - $time_start;
                        if($duration < 1) {
                            $object->logger($object->config('project.log.node'))->info('Duration: (10) ' . round($duration * 1000, 2) . ' msec url: ' . $options['url']);
                        } else {
                            $object->logger($object->config('project.log.node'))->info('Duration: (11) ' . round($duration, 2) . ' sec url: ' . $options['url']);
                        }
                    }
                    return (array) $data->data();
                }
            }
        }
        $index = 0;
        $start = $index;
        $end = $start + (int) $options['limit'];
        $page = [];
        $record_index = $index;
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_search_index($file, [
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
                'direction' => [],
                'url' => $options['url'],
            ]);
            if($record){
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                //add expose
                if(!empty($options['filter'])){
                    $record = $this->filter($record, $options['filter'], $options);
                }
                elseif(!empty($options['where'])){
                    $record = $this->where($record, $options['where'], $options);
                }
                if($record){
                    $record->{'#index'} = $record_index;
                    $page[] = $record;
                    $record_index++;
                } else {
                    $end++;
                }
            } else {
                break;
            }
        }
        if($object->config('project.log.node')){
            $time_end = microtime(true);
            $duration = $time_end - $time_start;
            if($duration < 1) {
                $object->logger($object->config('project.log.node'))->info('Duration: (6) ' . round($duration * 1000, 2) . ' msec url: ' . $options['url']);
            } else {
                $object->logger($object->config('project.log.node'))->info('Duration: (7) ' . round($duration, 2) . ' sec url: ' . $options['url']);
            }
        }
        if(
            array_key_exists('mtime', $options) &&
            array_key_exists('ramdisk', $options) &&
            $options['ramdisk'] === true &&
            $url
        ){
            $cache = new Storage($page);
            $write = $cache->write($url);
            File::touch($url, $options['mtime']);
            File::touch($options['url'], $options['mtime']);
        }
        return $page;
    }
    */

    /**
     * @throws ObjectException
     */
    /*
    private function binary_tree_node($line='', $options=[]){
        d($line);
        ddd($options);
        if(!array_key_exists('url_connect', $options)){
            return false;
        }
        if(!array_key_exists('url_connect_property', $options)){
            return false;
        }
        if(!array_key_exists('seek', $options)){
            return false;
        }


        ddd($options);
        /*
        if(!is_array($data)){
            return false;
        }
        foreach($data as $nr => $line){
            $data[$nr] = ltrim($line);
        }
        $data = implode('', $data);
        $record  = Core::object($data, Core::OBJECT_OBJECT);
        if(!is_object($record)){
            return false;
        }
        if(!property_exists($record, 'uuid')){
            return false;
        }
        if(!array_key_exists('counter', $options)){
            return false;
        }
        if(!array_key_exists('seek', $options)){
            return false;
        }
        if(!array_key_exists('lines', $options)){
            return false;
        }
        $record->{'#read'} = new stdClass();
        $record->{'#read'}->load = $options['counter'];
        $record->{'#read'}->seek = $options['seek'];
        $record->{'#read'}->lines = $options['lines'];
        $record->{'#read'}->percentage = round(($options['counter'] / $options['lines']) * 100, 2);
        $object = $this->object();
        $record->{'#read'}->url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Storage' .
            $object->config('ds') .
            substr($record->uuid, 0, 2) .
            $object->config('ds') .
            $record->uuid .
            $object->config('extension.json')
        ;
        return $record;
    }
    */

    /**
     * @throws ObjectException
     * @throws Exception
     */
    private function binary_tree_index($file, $file_uuid, $file_connect_property, $options=[]){
        if(!array_key_exists('counter', $options)){
            $options['counter'] = 0;
        }
        if(!array_key_exists('lines', $options)){
            return false;
        }
        if(!array_key_exists('index', $options)){
            return false;
        } else {
            $options['index'] = (float) $options['index'];
        }
        if(!array_key_exists('search', $options)){
            return false;
        }
        $object = $this->object();
        if(!array_key_exists('min', $options)){
            $options['min'] = 0;
        }
        if(!array_key_exists('max', $options)){
            $options['max'] = $options['lines'] - 1;
        }
        if(!array_key_exists('is_debug', $options)){
            $options['is_debug'] = false;
        }
        if(!array_key_exists('url_uuid', $options)){
            return false;
        }
        if(!array_key_exists('url_connect_property', $options)){
            return false;
        }
        $direction = 'up';
//        echo '--------------------------------------' . PHP_EOL;
        while($options['min'] <= $options['max']){
            $seek = $options['min'] + floor(($options['max'] - $options['min']) / 2);
            if(
                $direction === 'down' &&
                !in_array($seek, $options['search'], true)
            ){
                $options['search'][] = $seek;
                $options['direction'][] = $direction;
            }
            elseif($direction === 'down') {
                foreach($options['search'] as $nr => $search){
                    if($search === $seek){
                        if(array_key_exists($nr, $options['direction'])){
                            if($options['direction'][$nr] === 'down'){
                                //not found
                                return false;
                            }
                        }
                    }
                }
            }
            $file->seek($seek);
//            echo 'Seek: ' . $seek . ' options.index: ' . $options['index'] . PHP_EOL;
            $line = $file->current();
//            echo $line . PHP_EOL;
            $options['counter']++;
            if($options['counter'] > 1024){
                throw new Exception('Out of range');
                //log error with filesize of view
                break;
            }
            if ($options['index'] === $seek) {
                $options['line'] = $line;
//                echo 'Seek: ' . $seek . ' options.index: ' . $options['index'] . PHP_EOL;
                $uuid = $this->binary_tree_uuid($file, $file_uuid, $file_connect_property, $options);
//                echo 'UUID: ' . $uuid . PHP_EOL;
                if($uuid){
                    $record = [];
                    $record['uuid'] = $uuid;
                    $record['#read'] = [];
                    $record['#read']['load'] = $options['counter'];
                    $record['#read']['seek'] = $seek;
                    $record['#read']['lines'] = $options['lines'];
                    $record['#read']['percentage'] = round(($options['counter'] / $options['lines']) * 100, 2);
                    $record['#read']['url'] = $object->config('project.dir.data') .
                        'Node' .
                        $object->config('ds') .
                        'Storage' .
                        $object->config('ds') .
                        substr($record['uuid'], 0, 2) .
                        $object->config('ds') .
                        $record['uuid'] .
                        $object->config('extension.json')
                    ;
                    $record['#read'] = (object) $record['#read'];
                    return (object) $record;
                } else {
                    return false;
                }
            }
            elseif(
                $options['index'] < $seek
            ){
                $direction = 'up';
                $options['max'] = $seek - 1;
            }
            elseif(
                $options['index'] > $seek
            ){
                if(in_array($seek, $options['search'], true)){
                    $direction = 'down';
                } else {
                    $direction = 'up';
                }
                $options['min'] = $seek + 1;
            }
            if($direction === 'up'){
                $seek--;
                if($seek < 0){
                    $direction = 'down';
                    $seek = 0;
                }
                $file->seek($seek);
                $options['search'][] = $seek;
                $options['direction'][] = $direction;
            } else {
                $seek++;
                $options['search'][] = $seek;
                $options['direction'][] = $direction;
                $file->next();
                if($seek === $options['lines'] - 1){
                    $direction = 'up';
                }
            }
        }
        return false;
    }

    private function binary_tree_uuid($file, $file_uuid, $file_connect_property, $options=[]): ?string
    {
        $object = $this->object();
        if(
            $file_connect_property === null &&
            $file &&
            $file_uuid &&
            array_key_exists('url', $options) &&
            File::exist($options['url']) &&
            array_key_exists('url_uuid', $options) &&
            $options['url'] === $options['url_uuid'] &&
            array_key_exists('line', $options)
        ){
            return rtrim($options['line'], PHP_EOL);
        }
        elseif(
            array_key_exists('url_connect_property', $options) &&
            File::exist($options['url_connect_property']) &&
            array_key_exists('url_uuid', $options) &&
            File::exist($options['url_uuid'])
        ){
            $key = sha1($options['url_connect_property']);

            $file_connect_property->seek($options['index']);
            $file_connect_line = (float) (rtrim($file_connect_property->current(), PHP_EOL) + 0);
            $key = sha1($options['url_uuid']);
            $file_uuid->seek($file_connect_line);
            $file_uuid_line = $file_uuid->current();
            return rtrim($file_uuid_line, PHP_EOL);
        }
        return null;
    }
}