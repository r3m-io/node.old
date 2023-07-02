<?php

namespace R3m\Io\Node\Trait\Data;

use SplFileObject;

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
     * @throws \Exception
     */
    public function count($class, $role, $options=[]): false|int
    {
        d($class);
        d($role);
        d($options);
        $count = 0;
        $name = Controller::name($class);
        $options['function'] = 'list';
        $object = $this->object();
        $dir = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinaryTree' .
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
        if(!array_key_exists('relation', $options)){
            $options['relation'] = false; //maybe true (depends on speedtest)
        }
        if(!array_key_exists('sort', $options)){
            $options['sort'] = [
                'uuid' => 'ASC'
            ];
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
            d(File::exist($url));
            ddd($url);
            if(!File::exist($url)) {
                return false;
            }
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
                        $count = $this->binary_search_count(
                            $file,
                            $role,
                            [
                                'where' => $options['where'],
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
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
                    $count = false; //<-- remove this line to enable cache
                    if($count){
                        return $count;
                    } else {
                        $count = $this->binary_search_count(
                            $file,
                            $role,
                            [
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url,
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