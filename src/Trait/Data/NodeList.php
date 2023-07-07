<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Config;
use R3m\Io\Module\Dir;
use SplFileObject;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\File;
use R3m\Io\Module\Data as Storage;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait NodeList {

    public function url($dir, $options=[]): string
    {
        $object = $this->object();
        $properties = [];
        foreach($options['sort'] as $key => $order){
            if(empty($properties)){
                $properties[] = $key;
                $order = 'asc';
            } else {
                $properties[] = $key;
                $order = strtolower($order);
            }
            $dir .= ucfirst($order) . $object->config('ds');
        }
        $property = implode('-', $properties);
        return $dir .
            Controller::name($property) .
            $object->config('extension.btree')
        ;
    }

    public function has_descending($options=[]): bool
    {
        foreach($options['sort'] as $key => $order){
            if(strtolower($order) === 'desc'){
                return true;
            }
        }
        return false;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     * @throws \Exception
     */
    public function list($class, $role, $options=[]): false|array
    {
        $name = Controller::name($class);
        $options = Core::object($options, Core::OBJECT_ARRAY);
        if(!array_key_exists('function', $options)){
            $options['function'] = __FUNCTION__;
        }
        if(!array_key_exists('relation', $options)){
            $options['relation'] = true;
        }
        $object = $this->object();
        if(!array_key_exists('sort', $options)){
            throw new Exception('Sort is missing in options for ' . $name . '::' . $options['function'] . '()');
        }
        $first = true;
        $properties = [];
        $url_connect_key = '';
        foreach($options['sort'] as $key => $order){
            $properties[] = $key;
            if($first){
                $url_connect_key .= 'Asc' . $object->config('ds');
                $first = false;
            } else {
                $url_connect_key .= ucfirst(strtolower($order)) . $object->config('ds');
            }
        }
        $dir = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinaryTree' .
            $object->config('ds') .
            $name .
            $object->config('ds')
        ;
        $url = $this->url($dir, $options);
        $url_uuid = $dir . 'Asc' . $object->config('ds') . 'Uuid' . $object->config('extension.btree');
        $property = implode('-', $properties);
        $url_connect_property = $dir .
            $url_connect_key .
            Controller::name($property) .
            $object->config('extension.connect')
        ;
        $mtime = false;
        $ramdisk_url = false;
        if(
            File::exist($url) &&
            array_key_exists('ramdisk', $options) &&
            $options['ramdisk'] === true
        ){
            $mtime = File::mtime($url);
            $user_dir = $object->config('ramdisk.url') .
                $object->config(Config::POSIX_ID) .
                $object->config('ds')
            ;
            $package_dir = $user_dir .
                'Package' .
                $object->config('ds')
            ;
            $namespace_dir = $package_dir .
                'R3m-Io' .
                $object->config('ds')
            ;
            $ramdisk_dir = $namespace_dir .
                'Node' .
                $object->config('ds')
            ;
            $ramdisk_file = $name . '-' . $options['function'] . '-';
            $ramdisk_key = [
                'sort' => $options['sort'],
                'filter' => $options['filter'] ?? [],
                'where' => $options['where'] ?? [],
                'relation' => $options['relation'],
                'page' => $options['page'] ?? 1,
                'limit' => $options['limit'] ?? 1000,
                'mtime' => $mtime
            ];
            $ramdisk_key = sha1(Core::object($ramdisk_key, Core::OBJECT_JSON));
            $ramdisk_url = $ramdisk_dir .
                $ramdisk_file .
                $ramdisk_key .
                $object->config('extension.json')
            ;
            $ramdisk_data = $object->data_read($ramdisk_url, $ramdisk_key);
            if($ramdisk_data){
                //add mtime to ramdisk data
                return (array) $ramdisk_data->data();
            }
        }
        $this->binary_tree_list_create($class, $role, $options);
        if(!array_key_exists('where', $options)){
            $options['where'] = [];
        }
        if(!array_key_exists('filter', $options)){
            $options['filter'] = [];
        }
        if(!File::exist($url)){
            $list = [];
            $result = [];
            $result['page'] = $options['page'] ?? 1;
            $result['limit'] = $options['limit'] ?? 1000;
            $result['count'] = 0;
            $result['list'] = $list;
            $result['sort'] = $options['sort'];
            if(!empty($options['filter'])) {
                $result['filter'] = $options['filter'];
            }
            if(!empty($options['where'])) {
                $result['where'] = $options['where'];
            }
            $result['relation'] = $options['relation'];
            $result['mtime'] = $mtime;
            return $result;
        }
        if($mtime === false) {
            $mtime = File::mtime($url);
        }
        $has_descending = $this->has_descending($options);
        $list = [];
        $counter = 0;
        if(!$has_descending){
            $meta_url = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds') .
                'Meta' .
                $object->config('ds') .
                $class .
                $object->config('extension.json')
            ;
            $meta = $object->data_read($meta_url, sha1($meta_url));
            if(!$meta){
                return false;
            }
            if(!empty($options['filter'])){
                $key = [
                    'filter' => $options['filter'],
                    'sort' => $options['sort'],
                    'page' => $options['page'] ?? 1,
                    'limit' => $options['limit'] ?? 1000,
                    'mtime' => $mtime
                ];
                $key = sha1(Core::object($key, Core::OBJECT_JSON));
                $lines = $meta->get('Filter.' . $name . '.' . $key . '.lines');
                $count = $meta->get('Filter.' . $name . '.' . $key . '.count');
                $filter_url = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Filter' .
                    $object->config('ds') .
                    $name .
                    $object->config('ds') .
                    $key .
                    $object->config('extension.json')
                ;
                $filter_url = false;
                $filter_mtime = File::mtime($filter_url);
                if(
                    File::exist($filter_url) &&
                    $mtime === $filter_mtime &&
                    $lines >= 0
                ){
                    $list = $this->filter_nodelist($class, $role, [
                        'url' => $filter_url,
                        'lines' => $lines,
                        'count' => $count,
                        'key' => $key,
                        'function' => $options['function'],
                    ]);
                    $counter = $count;
                } else {
                    $sort_key = [
                        'property' => $properties,
                    ];
                    $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
                    $lines = $meta->get('Sort.' . $name . '.' . $sort_key . '.lines');
                    if(
                        File::exist($url) &&
                        $lines > 0
                    ){
                        $file = new SplFileObject($url);
                        $file_uuid = new splFileObject($url_uuid);
                        $file_connect_property =new splFileObject($url_connect_property);
                        $list = $this->binary_tree_page(
                            $file,
                            $file_uuid,
                            $file_connect_property,
                            $role,
                            $counter,
                            [
                                'filter' => $options['filter'],
                                'page' => $options['page'] ?? 1,
                                'limit' => $options['limit'] ?? 1000,
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
                                'url_uuid' => $url_uuid,
                                'url_connect_property' => $url_connect_property,
                                'function' => $options['function'],
                                'relation' => $options['relation'],
                                'name' => $name,
                                'ramdisk' => $options['ramdisk'] ?? false,
                                'mtime' => $mtime
                            ]
                        );
                    }
                }
                $result = [];
                $result['page'] = $options['page'] ?? 1;
                $result['limit'] = $options['limit'] ?? 1000;
                $result['count'] = $counter;
                $result['list'] = $list;
                $result['sort'] = $options['sort'];
                $result['filter'] = $options['filter'];
                $result['relation'] = $options['relation'];
                $result['mtime'] = $mtime;
                if($ramdisk_url){
                    $ramdisk_data = new Storage($result);
                    $ramdisk_data->write($ramdisk_url);
                    //sync_permission
                    $this->sync_file([
                        'user_dir' => $user_dir,
                        'package_dir' => $package_dir,
                        'namespace_dir' => $namespace_dir,
                        'ramdisk_dir' => $ramdisk_dir,
                        'ramdisk_url' => $ramdisk_url,
                    ]);
                }
                return $result;
            }
            elseif(!empty($options['where'])){
                $options['where'] = $this->where_convert($options['where']);
                $key = [
                    'where' => $options['where'],
                    'sort' => $options['sort'],
                    'page' => $options['page'] ?? 1,
                    'limit' => $options['limit'] ?? 1000,
                    'mtime' => $mtime
                ];
                $key = sha1(Core::object($key, Core::OBJECT_JSON));
                $lines = $meta->get('Where.' . $name . '.' . $key . '.lines');
                $where_url = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Where' .
                    $object->config('ds') .
                    $name .
                    $object->config('ds') .
                    $key .
                    $object->config('extension.json')
                ;
                $where_mtime = File::mtime($where_url);
                if(
                    File::exist($where_url) &&
                    $mtime === $where_mtime &&
                    $lines >= 0
                ){
                    $file = new SplFileObject($where_url);
                    $file_uuid = new splFileObject($url_uuid);
                    $file_connect_property =new splFileObject($url_connect_property);
                    $where = [];
                    $list = $this->binary_tree_page(
                        $file,
                        $file_uuid,
                        $file_connect_property,
                        $role,
                        $counter,
                        [
                            'where' => $where,
                            'page' => $options['page'] ?? 1,
                            'limit' => $options['limit'] ?? 1000,
                            'lines'=> $lines,
                            'counter' => 0,
                            'direction' => 'next',
                            'url' => $where_url,
                            'url_uuid' => $url_uuid,
                            'url_connect_property' => $url_connect_property,
                            'function' => $options['function'],
                            'relation' => $options['relation'],
                            'name' => $name,
                            'ramdisk' => $options['ramdisk'] ?? false,
                            'mtime' => $mtime
                        ]
                    );
                } else {
                    $sort_key = [
                        'property' => $properties,
                    ];
                    $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
                    $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                    if(
                        File::exist($url) &&
                        $lines > 0
                    ){
                        $file = new SplFileObject($url);
                        $file_uuid = new splFileObject($url_uuid);
                        $file_connect_property =new splFileObject($url_connect_property);
                        $list = $this->binary_tree_page(
                            $file,
                            $file_uuid,
                            $file_connect_property,
                            $role,
                            $counter,
                            [
                                'where' => $options['where'],
                                'page' => $options['page'] ?? 1,
                                'limit' => $options['limit'] ?? 1000,
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
                                'url_uuid' => $url_uuid,
                                'url_connect_property' => $url_connect_property,
                                'function' => $options['function'],
                                'relation' => $options['relation'],
                                'name' => $name,
                                'ramdisk' => $options['ramdisk'] ?? false,
                                'mtime' => $mtime
                            ]
                        );
                    }
                }
                $result = [];
                $result['page'] = $options['page'] ?? 1;
                $result['limit'] = $options['limit'] ?? 1000;
                $result['count'] = $counter;
                $result['list'] = $list;
                $result['sort'] = $options['sort'];
                $result['where'] = $options['where'];
                $result['relation'] = $options['relation'];
                $result['mtime'] = $mtime;
                if($ramdisk_url){
                    $ramdisk_data = new Storage($result);
                    $ramdisk_data->write($ramdisk_url);
                    $this->sync_file([
                        'user_dir' => $user_dir,
                        'package_dir' => $package_dir,
                        'namespace_dir' => $namespace_dir,
                        'ramdisk_dir' => $ramdisk_dir,
                        'ramdisk_url' => $ramdisk_url,
                    ]);
                }
                return $result;
            } else {
                // no filter, no where
                $url_key = 'url.';
                $first = true;
                foreach($options['sort'] as $key => $order) {
                    if($first){
                        $url_key .= 'asc.';
                        $first = false;
                    } else {
                        $url_key .= strtolower($order) . '.';
                    }
                }
                $url_key = substr($url_key, 0, -1);
                $sort_key = [
                    'property' => $properties
                ];
                $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
                $url = $meta->get('Sort.' . $class . '.' . $sort_key . '.'. $url_key);
                $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                if(
                    File::exist($url) &&
                    $lines > 0
                ){
                    $file = new SplFileObject($url);
                    $file_uuid = new splFileObject($url_uuid);
                    $file_connect_property =new splFileObject($url_connect_property);
                    $list = $this->binary_tree_page(
                        $file,
                        $file_uuid,
                        $file_connect_property,
                        $role,
                        $counter,
                        [
                            'page' => $options['page'] ?? 1,
                            'limit' => $options['limit'] ?? 1000,
                            'lines'=> $lines,
                            'counter' => 0,
                            'direction' => 'next',
                            'url' => $url,
                            'url_uuid' => $url_uuid,
                            'url_connect_property' => $url_connect_property,
                            'function' => $options['function'],
                            'relation' => $options['relation'],
                            'name' => $name,
                            'ramdisk' => $options['ramdisk'] ?? false,
                            'mtime' => $mtime
                        ]
                    );
                    $result = [];
                    $result['page'] = $options['page'] ?? 1;
                    $result['limit'] = $options['limit'] ?? 1000;
                    $result['count'] = $counter;
                    $result['list'] = $list;
                    $result['sort'] = $options['sort'];
                    $result['relation'] = $options['relation'];
                    $result['mtime'] = $mtime;
                    if($ramdisk_url){
                        $ramdisk_data = new Storage($result);
                        $ramdisk_data->write($ramdisk_url);
                        $this->sync_file([
                            'user_dir' => $user_dir,
                            'package_dir' => $package_dir,
                            'namespace_dir' => $namespace_dir,
                            'ramdisk_dir' => $ramdisk_dir,
                            'ramdisk_url' => $ramdisk_url,
                        ]);
                    }
                    return $result;
                }
            }
        }
        return false;
    }

    public function list_attribute($list=[], $attribute=[]): array
    {
        $response = [];
        if(!is_array($list)){
            return $response;
        }
        foreach($list as $nr => $record){
            foreach($attribute as $item){
                if(
                    is_array($record) &&
                    array_key_exists($item, $record)
                ){
                    $response[$nr][$item] = $record[$item];
                }
                elseif(
                    is_object($record) &&
                    property_exists($record, $item)
                ){
                    $response[$nr][$item] = $record->{$item};
                }
            }
        }
        return $response;
    }
}