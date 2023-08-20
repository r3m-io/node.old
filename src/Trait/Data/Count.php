<?php

namespace R3m\Io\Node\Trait\Data;

use SplFileObject;

use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\File;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Count {
    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function count($class, $role, $options=[]): false|int
    {
        $options = Core::object($options, Core::OBJECT_ARRAY);
        $count = 0;
        d($class);
        $name = Controller::name($class);
        $options['function'] = 'list';
        $object = $this->object();
        $dir = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinaryTree' .
            $object->config('ds') .
            $name .
            $object->config('ds')
        ;
        $url_uuid = $dir . 'Asc' . $object->config('ds') . 'Uuid' . $object->config('extension.btree');
        if(!array_key_exists('where', $options)){
            $options['where'] = [];
        }
        if(!array_key_exists('filter', $options)){
            $options['filter'] = [];
        }
        if(!array_key_exists('relation', $options)){
            $options['relation'] = false; //maybe true (depends on speedtest)
        }
        if(!array_key_exists('sort', $options)){
            $options['sort'] = [
                'uuid' => 'ASC'
            ];
        }
        //command line nested to not nested hack.
        $sort_data = new Storage();
        $sort_data->do_not_nest_key(true);
        $sort_data->data($options['sort']);
        $sort_patch = $sort_data->patch_nested_key();
        $options['sort'] = $sort_patch;
        d($options);
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
                $object->config('extension.btree')
            ;
            $url_connect_property = $dir .
                Controller::name($property) .
                $object->config('extension.connect')
            ;
            if(!File::exist($url)) {
                //logger exception
                return false;
            }

            if(!File::exist($url_uuid)) {
                //logger exception
                return false;
            }
            if(!File::exist($url_connect_property)) {
                //logger exception
                return false;
            }
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
                return false;
            }
            $sort_key = [
                'property' => $properties
            ];
            $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
            $lines = $meta->get('Sort.' . $name . '.' . $sort_key . '.lines');
            if(
                File::exist($url) &&
                $lines > 0
            ){
                $file = new SplFileObject($url);
                $mtime = File::mtime($url);
                if(!empty($options['filter'])){
                    $count_key = [
                        'properties' => $properties,
                        'filter' => $options['filter'],
                        'mtime' => $mtime
                    ];
                    $count_key = sha1(Core::object($count_key, Core::OBJECT_JSON));
                    $count = $meta->get('Count.' . $name . '.' . $count_key . '.count');
                    if($count){
                        return $count;
                    } else {
                        $count = $this->binary_tree_count(
                            $file,
                            $role,
                            [
                                'filter' => $options['filter'],
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
                                'url_uuid' => $url_uuid,
                                'url_connect_property' => $url_connect_property,
                                'function' => $options['function'],
                                'relation' => $options['relation']
                            ]
                        );
                        $meta->set('Count.' . $name . '.' . $count_key . '.count', $count);
                        $meta->set('Count.' . $name . '.' . $count_key . '.mtime', $mtime);
                        $meta->write($meta_url);
                    }
                }
                elseif(!empty($options['where'])){
                    $count_key = [
                        'properties' => $properties,
                        'where' => $options['where'],
                        'mtime' => $mtime
                    ];
                    $count_key = sha1(Core::object($count_key, Core::OBJECT_JSON));
                    $count = $meta->get('Count.' . $name . '.' . $count_key . '.count');
                    if($count){
                        return $count;
                    } else {
                        $count = $this->binary_tree_count(
                            $file,
                            $role,
                            [
                                'where' => $options['where'],
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
                                'url_uuid' => $url_uuid,
                                'url_connect_property' => $url_connect_property,
                                'function' => $options['function'],
                                'relation' => $options['relation']
                            ]
                        );
                        $meta->set('Count.' . $name . '.' . $count_key . '.count', $count);
                        $meta->set('Count.' . $name . '.' . $count_key . '.mtime', $mtime);
                        $meta->write($meta_url);
                    }

                } else {
                    $count_key = [
                        'properties' => $properties,
                        'mtime' => $mtime
                    ];
                    $count_key = sha1(Core::object($count_key, Core::OBJECT_JSON));
                    $count = $meta->get('Count.' . $name . '.' . $count_key . '.count');
                    $sort_count = $meta->get('Sort.' . $name . '.' . $sort_key . '.count');
                    if($count){
                        return $count;
                    }
                    elseif($sort_count){
                        return $sort_count;
                    } else {
                        $count = $this->binary_tree_count(
                            $file,
                            $role,
                            [
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
                                'url_uuid' => $url_uuid,
                                'url_connect_property' => $url_connect_property,
                                'function' => $options['function'],
                                'relation' => $options['relation']
                            ]
                        );
                        $meta->set('Count.' . $name . '.' . $count_key . '.count', $count);
                        $meta->set('Count.' . $name . '.' . $count_key . '.mtime', $mtime);
                        $meta->write($meta_url);
                    }
                }
            }
            return $count;
        }
        return false;
    }
}