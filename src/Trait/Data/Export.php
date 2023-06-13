<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Core;
use R3m\Io\Module\File;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;


Trait Export {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function export($class, $role, $options=[]){
        if(!array_key_exists('url', $options)){
            return;
        }
        if(File::exist($options['url'])){
            return;
        }
        $name = Controller::name($class);
        $object = $this->object();
        $meta_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $meta = $object->data_read($meta_url);
        $list_options = [
            'sort' => [
                'uuid' => 'asc'
            ]
        ];

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
        $count = $meta->get('Sort.' . $class . '.' . $sort_key . '.'. 'count');
        d($url_property);
        d($count);
        ddd($meta);


        $list_options = [];

        $list = $this->list($class, $role, $list_options);


        d($class);
        d($role);
        ddd($options);

        $object = $this->object();
        $data = $object->data_read($options['url']);

        /*
        if($data){
            $create_many = $this->create_many($class, $data);
            ddd($create_many);
        }
        */
    }
}
