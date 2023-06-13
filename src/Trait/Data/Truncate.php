<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Core;
use R3m\Io\Module\File;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;


Trait Truncate {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function truncate($class, $role, $options=[]): void
    {
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
            ],
            'limit' => $options['limit'] ?? 1000,
        ];
        $properties = [];
        $url_key = 'url.';
        if(!array_key_exists('sort', $list_options)){
            $debug = debug_backtrace(true);
            ddd($debug[0]['file'] . ' ' . $debug[0]['line']);
        }
        foreach($list_options['sort'] as $key => $order) {
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
        $count = $meta->get('Sort.' . $name . '.' . $sort_key . '.' . 'count');
//        $url_property = $meta->get('Sort.' . $name . '.' . $sort_key . '.' . $url_key);
        $page_max = ceil($count / $list_options['limit']);
        for($page=1; $page <= $page_max; $page++) {
            $list_options['page'] = $page;
            $response = $this->list($name, $role, $list_options);
            $list = [];
            foreach ($response['list'] as $record) {
                $list[] = $record;
            }
            foreach($list as $record){
                if(
                    is_array($record) &&
                    array_key_exists('uuid', $record)
                ){
                    $delete = $this->delete(
                        $name,
                        $role,
                        [
                            'uuid' => $record['uuid']
                        ]
                    );
                    ddd($delete);
                }
                elseif(
                    is_object($record) &&
                    property_exists($record, 'uuid')
                ){
                    $delete = $this->delete(
                        $name,
                        $role,
                        [
                        'uuid' => $record->uuid
                        ]
                    );
                    ddd($delete);
                }
            }
            ddd($list);
        }
    }
}
