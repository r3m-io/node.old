<?php

namespace R3m\Io\Node\Trait\Data;

use SplFileObject;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\File;

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
            $object->config('extension.json')
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
        $dir = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinarySearch' .
            $object->config('ds') .
            $class .
            $object->config('ds')
        ;
        $url = $this->url($dir, $options);
        $mtime = false;
        if(
            File::exist($url) &&
            array_key_exists('ramdisk', $options) &&
            $options['ramdisk'] === true
        ){
            $mtime = File::mtime($url);
            $package_dir = $object->config('ramdisk.url') .
                'Package' . $object->config('ds');
            $namespace_dir = $package_dir . 'R3m-Io' . $object->config('ds');
            $ramdisk_dir = $namespace_dir .
                'Node' . $object->config('ds')
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
            $ramdisk_data = $object->data_read($ramdisk_url);
            if($ramdisk_data){
                return $ramdisk_data->data();
            }
        }
        $this->binary_search_list_create($class, $options);

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
            $result['list'] = $list;
            $result['sort'] = $options['sort'];
            if(!empty($options['filter'])) {
                $result['filter'] = $options['filter'];
            }
            if(!empty($options['where'])) {
                $result['where'] = $options['where'];
            }
            $result['relation'] = $options['relation'];
            return $result;
        }
        if($mtime === false) {
            $mtime = File::mtime($url);
        }
        $has_descending = $this->has_descending($options);
        $list = [];
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
                $filter_mtime = File::mtime($filter_url);
                if(
                    File::exist($filter_url) &&
                    $mtime === $filter_mtime &&
                    $lines >= 0
                ){
                    $file = new SplFileObject($filter_url);
//                        $options['filter']['#key'] = $key;
                    $list = $this->binary_search_page(
                        $file,
                        $role,
                        [
                            'filter' => $options['filter'],
                            'page' => $options['page'] ?? 1,
                            'limit' => $options['limit'] ?? 1000,
                            'lines'=> $lines,
                            'counter' => 0,
                            'direction' => 'next',
                            'url' => $filter_url,
                            'function' => $options['function'],
                            'relation' => $options['relation']
                        ]
                    );
                } else {
                    $sort_key = [
//                        'filter' => $options['filter'],
                        'sort' => $options['sort'],
                        'page' => $options['page'] ?? 1,
                        'limit' => $options['limit'] ?? 1000,
                        'mtime' => $mtime
                    ];
                    $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
                    $lines = $meta->get('Sort.' . $name . '.' . $sort_key . '.lines');
                    if(
                        File::exist($url) &&
                        $lines > 0
                    ){
                        $file = new SplFileObject($url);
                        $list = $this->binary_search_page(
                            $file,
                            $role,
                            [
                                'filter' => $options['filter'],
                                'page' => $options['page'] ?? 1,
                                'limit' => $options['limit'] ?? 1000,
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
                                'function' => $options['function'],
                                'relation' => $options['relation']
                            ]
                        );
                    }
                }
                $result = [];
                $result['page'] = $options['page'] ?? 1;
                $result['limit'] = $options['limit'] ?? 1000;
                $result['list'] = $list;
                $result['sort'] = $options['sort'];
                $result['filter'] = $options['filter'];
                $result['relation'] = $options['relation'];
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
                    $where = [];
                    $list = $this->binary_search_page(
                        $file,
                        $role,
                        [
                            'where' => $where,
                            'page' => $options['page'] ?? 1,
                            'limit' => $options['limit'] ?? 1000,
                            'lines'=> $lines,
                            'counter' => 0,
                            'direction' => 'next',
                            'url' => $where_url,
                            'function' => $options['function'],
                            'relation' => $options['relation']
                        ]
                    );
                } else {
                    $sort_key = [
//                        'where' => $options['where'],
                        'sort' => $options['sort'],
                        'page' => $options['page'] ?? 1,
                        'limit' => $options['limit'] ?? 1000,
                        'mtime' => $mtime
                    ];
                    $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
                    $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                    if(
                        File::exist($url) &&
                        $lines > 0
                    ){
                        $file = new SplFileObject($url);
                        $list = $this->binary_search_page(
                            $file,
                            $role,
                            [
                                'where' => $options['where'],
                                'page' => $options['page'] ?? 1,
                                'limit' => $options['limit'] ?? 1000,
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
                                'function' => $options['function'],
                                'relation' => $options['relation']
                            ]
                        );
                    }
                }
                $result = [];
                $result['page'] = $options['page'] ?? 1;
                $result['limit'] = $options['limit'] ?? 1000;
                $result['list'] = $list;
                $result['sort'] = $options['sort'];
                $result['where'] = $options['where'];
                $result['relation'] = $options['relation'];
                return $result;
            } else {
                // no filter, no where
                $sort_key = [
                    'sort' => $options['sort'],
                    'page' => $options['page'] ?? 1,
                    'limit' => $options['limit'] ?? 1000,
                    'mtime' => $mtime
                ];
                $url_key = 'url.';
                if(
                    array_key_exists('sort', $options) &&
                    is_array($options['sort'])
                ){
                    foreach($options['sort'] as $key => $order) {
                        if(empty($properties)){
                            $url_key .= 'asc.';
                        } else {
                            $url_key .= strtolower($order) . '.';
                        }
                    }
                }
                $url_key = substr($url_key, 0, -1);
                $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
                $url = $meta->get('Sort.' . $class . '.' . $sort_key . '.'. $url_key);
                $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                if(
                    File::exist($url) &&
                    $lines > 0
                ){
                    $file = new SplFileObject($url);
                    $list = $this->binary_search_page(
                        $file,
                        $role,
                        [
                            'page' => $options['page'] ?? 1,
                            'limit' => $options['limit'] ?? 1000,
                            'lines'=> $lines,
                            'counter' => 0,
                            'direction' => 'next',
                            'url' => $url,
                            'function' => $options['function'],
                            'relation' => $options['relation']
                        ]
                    );
                    $result = [];
                    $result['page'] = $options['page'] ?? 1;
                    $result['limit'] = $options['limit'] ?? 1000;
                    $result['list'] = $list;
                    $result['sort'] = $options['sort'];
                    $result['relation'] = $options['relation'];
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