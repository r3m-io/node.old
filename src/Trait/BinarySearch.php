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

Trait BinarySearch {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    private function binary_search_list_create($class, $options=[]): void
    {
        $object = $this->object();
        $name = Controller::name($class);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds');
        $dir_binarysearch = $dir_node .
            'BinarySearch' .
            $object->config('ds') .
            $name .
            $object->config('ds')
        ;
        $url = $dir_binarysearch .
            'Asc' .
            $object->config('ds') .
            'Uuid' .
            $object->config('extension.json')
        ;
        $meta_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $data = $object->data_read($url);
        if(!$data){
            return;
        }
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
        if(!array_key_exists('sort', $options)){
            $debug = debug_backtrace(true);
            ddd($debug[0]['file'] . ' ' . $debug[0]['line']);
        }
        foreach($options['sort'] as $key => $order) {
            if(empty($properties)){
                $url_key .= 'asc.';
            } else {
                $url_key .= strtolower($order) . '.';
            }
            $properties[] = $key;
        }
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
            $limit = $meta->get('Filter.' . $class . '.' . $key . '.limit') ?? 1000;
            $filter_list = $this->binary_search_list($file, [
                'filter' => $options['filter'],
                'limit' => $limit,
                'lines'=> $sort_lines,
                'counter' => 0,
                'direction' => 'next',
                'url' => $url_property,
            ]);
            if(!empty($filter_list)){
                $filter = [];
                $index = false;
                foreach($filter_list as $index => $node){
                    $filter[$key][$index] = [
                        'uuid' => $node->uuid,
                        '#index' => $index,
//                        '#key' => $key
                    ];
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
                $meta->set('Filter.' . $class . '.' . $key . '.lines', $lines);
                $meta->set('Filter.' . $class . '.' . $key . '.count', $count);
                $meta->set('Filter.' . $class . '.' . $key . '.limit', $limit);
                $meta->set('Filter.' . $class . '.' . $key . '.mtime', $mtime);
                $meta->set('Filter.' . $class . '.' . $key . '.filter', $options['filter']);
                $meta->set('Filter.' . $class . '.' . $key . '.sort', $options['sort']);
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
            $limit = $meta->get('Where.' . $class . '.' . $key . '.limit') ?? 1000;
            $where_list = $this->binary_search_list($file, [
                'where' => $options['where'],
                'limit' => $limit,
                'lines'=> $sort_lines,
                'counter' => 0,
                'direction' => 'next',
                'url' => $url_property,
            ]);
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
                $meta->set('Where.' . $class . '.' . $key . '.lines', $lines);
                $meta->set('Where.' . $class . '.' . $key . '.count', $count);
                $meta->set('Where.' . $class . '.' . $key . '.limit', $limit);
                $meta->set('Where.' . $class . '.' . $key . '.mtime', $mtime);
                $meta->set('Where.' . $class . '.' . $key . '.where', $options['where']);
                $meta->set('Where.' . $class . '.' . $key . '.sort', $options['sort']);
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

    private function relation_inner($relation, $data=[], $options=[], &$counter=0): false|array|stdClass
    {
        $object = $this->object();
        $counter++;
        if($counter > 1024){
            $is_loaded = $object->data('R3m.Io.Node.BinarySearch.relation');
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

                            $is_loaded = $object->data('R3m.Io.Node.BinarySearch.relation');
                            if(empty($is_loaded)){
                                $is_loaded = [];
                            }
                            if($relation_data->has('#class')){
                                $is_loaded[] = $relation_data->get('#class');
                                $object->data('R3m.Io.Node.BinarySearch.relation', $is_loaded);
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
                                    $selected = $this->relation_inner($relation_object_relation_data, $selected, $options, $counter);
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

                        $is_loaded = $object->data('R3m.Io.Node.BinarySearch.relation');
                        if(empty($is_loaded)){
                            $is_loaded = [];
                        }
                        if($relation_data->has('#class')){
                            $is_loaded[] = $relation_data->get('#class');
                            $object->data('R3m.Io.Node.BinarySearch.relation', $is_loaded);
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
                                $selected = $this->relation_inner($relation_object_relation_data, $selected, $options, $counter);
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
    private function relation($record, $data, $role, $options=[]){
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
                                                    $relation_data_data = $this->relation_inner($relation_relation, $relation_data_data, $options);
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
                                                            $relation_data_data = $this->relation_inner($relation_relation, $relation_data_data, $options);
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
                                                    $relation_data_data = $this->relation_inner($relation_relation, $relation_data_data, $options);
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
    private function binary_search_page($file, $role, &$counter=0, $options=[]): array
    {
        $object = $this->object();
        $index = 0;
        if(
            array_key_exists('page', $options) &&
            array_key_exists('limit', $options)
        ){
            $index = ($options['page'] * $options['limit']) - $options['limit'];
        }
        $start = $index;
        $end = $start + $options['limit'];
        $page = [];
        $time_start = microtime(true);
        $record_index = $index;
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_search_index($file, [
//                'page' => $options['page'],
//                'limit' => $options['limit'],
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
                'url' => $options['url'],
            ]);
            if(
                $record
            ){
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
                $record = $this->relation($record, $object_data, $role, $options);
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
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        if($duration < 1) {
            echo 'Duration: ' . round($duration * 1000, 2) . ' msec url: ' . $options['url'] . PHP_EOL;
        } else {
            echo 'Duration: ' . round($duration, 2) . ' sec url:' . $options['url'] . PHP_EOL;
        }
        return $page;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    private function binary_search_count($file, $role, $options=[]): int
    {
        $object = $this->object();
        $index = 0;
        $count = 0;
        $time_start = microtime(true);
        while(true){
            $record = $this->binary_search_index($file, [
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $index,
                'search' => [],
                'url' => $options['url'],
            ]);
            if($record){
                $index++;
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                if(!property_exists($record, '#class')){
                    //need to trigger sync
                    continue;
                }
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
                $record = $this->relation($record, $object_data, $role, $options);
                $expose = $this->expose_get(
                    $object,
                    $class,
                    $class . '.' . $options['function'] . '.expose'
                );
                $record = $this->expose(
                    new Storage($record),
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
            } else {
                break;
            }
        }
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        if($duration < 1) {
            echo 'Duration: ' . round($duration * 1000, 2) . ' msec url: ' . $options['url'] . PHP_EOL;
        } else {
            echo 'Duration: ' . round($duration, 2) . ' sec url:' . $options['url'] . PHP_EOL;
        }
        return $count;
    }

    /**
     * @throws Exception
     */
    /*
    private function binary_search_one($file, $options=[]): array
    {
        $object = $this->object();
        $index = 0;
        $options['page'] = 1;
        $options['limit'] = 1;
        $start = $index;
        $end = $start + $options['limit'];
        $page = [];
        $time_start = microtime(true);
        $record_index = $index;
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_search_index($file, [
                'page' => $options['page'],
                'limit' => $options['limit'],
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
                'url' => $options['url'],
            ]);
            if($record){
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
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
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        if($duration < 1) {
            echo 'Duration: ' . round($duration * 1000, 2) . ' msec' . PHP_EOL;
        } else {
            echo 'Duration: ' . round($duration, 2) . ' sec' . PHP_EOL;
        }
        return $page;
    }
    */

    /**
     * @throws Exception
     */
    private function binary_search_list($file, $options=[]): array
    {
        if(!array_key_exists('limit', $options)){
            return [];
        }
        if(!array_key_exists('lines', $options)){
            return [];
        }
        $object = $this->object();
        $index = 0;
        $start = $index;
        $end = $start + (int) $options['limit'];
        $page = [];
        $record_index = $index;
        $time_start = microtime(true);
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
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        if($duration < 1) {
            echo 'Duration: ' . round($duration * 1000, 2) . ' msec url: ' . $options['url'] . PHP_EOL;
        } else {
            echo 'Duration: ' . round($duration, 2) . ' sec url:' . $options['url'] . PHP_EOL;
        }
        return $page;
    }

    private function parse_index($data=[]): false|int
    {
        foreach($data as $nr => $line){
            if(strpos($line, '#index') !== false){
                $line = str_replace('"#index"', '', $line);
                $line = trim($line, " :,\n");
                return (int) $line;
            }
        }
        return false;
    }

    /**
     * @throws ObjectException
     */
    private function binary_search_node($data=[], $options=[]){
        if(!is_array($data)){
            return false;
        }
        foreach($data as $nr => $line){
            $data[$nr] = ltrim($line);
        }
        $data = implode('', $data);
        d($data);
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

    /**
     * @throws ObjectException
     */
    private function binary_search_index($file, $options=[]){
        $object = $this->object();
        if(!array_key_exists('counter', $options)){
            $options['counter'] = 0;
        }
        if(!array_key_exists('lines', $options)){
            return false;
        }
        if(!array_key_exists('index', $options)){
            return false;
        }
        if(!array_key_exists('search', $options)){
            return false;
        }
        if(!array_key_exists('min', $options)){
            $options['min'] = 0;
        }
        if(!array_key_exists('max', $options)){
            $options['max'] = $options['lines'] - 1;
        }
        $direction = 'up';
        $is_per_line = false;
//        echo '--------------------------------------' . PHP_EOL;
        while($options['min'] <= $options['max']){
            if($is_per_line === false){
                $seek = $options['min'] + floor(($options['max'] - $options['min']) / 2);
            } else {
                if($options['max'] >= $options['min']){
                    $seek = $options['min'];
                } else {
                    return false;
                }
            }
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
            $depth = false;
            $is_collect = false;
            $data = [];
            while($line = $file->current()){
                $options['counter']++;
                if($options['counter'] > 1024){
                    //log error with filesize of view
                    break 2;
                }
                $line_match = str_replace(' ', '', $line);
                $line_match = str_replace('"', '', $line_match);
                $explode = explode(':', $line_match);
//                echo $seek . ', ' . $direction . ', ' . $line . PHP_EOL;
                $symbol = trim($explode[0], " \t\n\r\0\x0B,");
                $symbol_right = null;
                if(array_key_exists(1, $explode)){
                    $symbol_right = trim($explode[1], " \t\n\r\0\x0B,");
                }
                if(
                    $symbol === '{' &&
                    !empty($seek)
                ){
                    $depth = 0;
                    $direction = 'down';
                    $is_collect = true;
                }
                if(
                    $depth !== false &&
                    $symbol === '}' ||
                    $symbol_right === '}'
                ){
//                    echo $symbol . '-' . $symbol_right . '-' . $depth . PHP_EOL;
                    $depth--;
                    if($depth === 0){
                        $data[] = $symbol;
                        $index = $this->parse_index($data);
                        if($index === false){
                            $object->logger($object->config('project.log.name'))->error('Cannot find index (' . $index .')in view: ' . $options['url'], $data);
                            return false;
                        }
                        if ($options['index'] === $index) {
                            return $this->binary_search_node(
                                $data,
                                [
                                    'seek' => $seek,
                                    ...$options
                                ]
                            );
                        }
                        elseif(
                            $options['index'] < $index
                        ){
                            $direction = 'up';
                            $options['max'] = $seek - 1;
                            break;
                        }
                        elseif(
                            $options['index'] > $index
                        ){
                            if(in_array($seek, $options['search'], true)){
                                $direction = 'down';
                                $is_per_line = true;
                            }
                            elseif(!$is_per_line) {
                                $direction = 'up';
                            }
                            $options['min'] = $seek + 1;
                            break;
                        }
                    }
                }
                elseif(
                    $depth !== false &&
                    $symbol === '{' ||
                    $symbol_right === '{'
                ){
                    $depth++;
//                    echo $symbol . '-' . $symbol_right . '-' . $depth . PHP_EOL;
                }
                if($is_collect){
                    $data[]= $line;
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
        }
        return false;
    }
}