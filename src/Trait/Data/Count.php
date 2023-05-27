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
        $count = 0;
        $name = Controller::name($class);
        $options['function'] = __FUNCTION__;
        $object = $this->object();
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
        if(!array_key_exists('relation', $options)){
            $options['relation'] = false;
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
            $sort_key = sha1(Core::object($properties, Core::OBJECT_JSON));
            $lines = $meta->get('Sort.' . $name . '.' . $sort_key . '.lines');
            if(
                File::exist($url) &&
                $lines > 0
            ){
                $file = new SplFileObject($url);
                if(!empty($options['filter'])){
                    $count = $this->binary_search_count(
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
                    d($count);
                }
                elseif(!empty($options['where'])){
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
                }
            }
            ddd($count);
        }
        return false;
    }
}