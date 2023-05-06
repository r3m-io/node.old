<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\File;
use SplFileObject;

Trait NodeList {
    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function list($class='', $options=[]): false|array
    {
        $name = Controller::name($class);
        $options = Core::object($options, Core::OBJECT_ARRAY);
        $function = __FUNCTION__;
        $object = $this->object();
        $this->binary_search_list_create($object, $class, $options);
        $dir = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinarySearch' .
            $object->config('ds') .
            $class .
            $object->config('ds')
        ;
        if(!array_key_exists('where', $options)){
            $options['where'] = [];
        }
        if(!array_key_exists('filter', $options)){
            $options['filter'] = [];
        }
        if(array_key_exists('sort', $options)){
            $properties = [];
            $has_descending = false;
            foreach($options['sort'] as $key => $order){
                if(empty($properties)){
                    $properties[] = $key;
                    $order = 'asc';
                } else {
                    $properties[] = $key;
                    $order = strtolower($order);
                    if($order === 'desc'){
                        $has_descending = true;
                    }
                }
                $dir .= ucfirst($order) . $object->config('ds');
            }
            $property = implode('-', $properties);
            $url = $dir .
                Controller::name($property) .
                $object->config('extension.json')
            ;
            if(!File::exist($url)){
                return false;
            }
            $mtime = File::mtime($url);
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
                        'sort' => $options['sort']
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
                        $options['filter']['#key'] = $key;
                        $list = $this->binary_search_page(
                            $object,
                            $file,
                            [
                                'filter' => $options['filter'],
                                'page' => $options['page'],
                                'limit' => $options['limit'],
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $filter_url,
                                'debug' => true
                            ]
                        );
                    } else {
                        $sort_key = sha1(Core::object($properties, Core::OBJECT_JSON));
                        $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                        if(
                            File::exist($url) &&
                            $lines > 0
                        ){
                            $file = new SplFileObject($url);
                            $list = $this->binary_search_page(
                                $object,
                                $file,
                                [
                                    'filter' => $options['filter'],
                                    'page' => $options['page'],
                                    'limit' => $options['limit'],
                                    'lines'=> $lines,
                                    'counter' => 0,
                                    'direction' => 'next',
                                    'url' => $url
                                ]
                            );
                        }

                    }
                    $result = [];
                    $result['page'] = $options['page'];
                    $result['limit'] = $options['limit'];
                    $result['list'] = $list;
                    $result['sort'] = $options['sort'];
                    $result['filter'] = $options['filter'] ?? [];
                    return $result;
                }
                elseif(!empty($options['where'])){
                    $options['where'] = $this->where_convert($options['where']);
                    $key = [
                        'where' => $options['where'],
                        'sort' => $options['sort']
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
                        $where[] = [
                            'value' => $key,
                            'attribute' => '#key',
                            'operator' => '==='
                        ];
                        $list = $this->binary_search_page(
                            $object,
                            $file,
                            [
                                'where' => $where,
                                'page' => $options['page'],
                                'limit' => $options['limit'],
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $where_url,
                                'debug' => true
                            ]
                        );
                    } else {
                        $sort_key = sha1(Core::object($properties, Core::OBJECT_JSON));
                        $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                        if(
                            File::exist($url) &&
                            $lines > 0
                        ){
                            $file = new SplFileObject($url);
                            $list = $this->binary_search_page(
                                $object,
                                $file,
                                [
                                    'where' => $options['where'],
                                    'page' => $options['page'],
                                    'limit' => $options['limit'],
                                    'lines'=> $lines,
                                    'counter' => 0,
                                    'direction' => 'next',
                                    'url' => $url
                                ]
                            );
                        }
                    }
                    $result = [];
                    $result['page'] = $options['page'];
                    $result['limit'] = $options['limit'];
                    $result['list'] = $list;
                    $result['sort'] = $options['sort'];
                    $result['where'] = $options['where'] ?? [];
                    return $result;
                } else {
                    ddd($options);
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