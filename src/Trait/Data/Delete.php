<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\File;

Trait Delete {
    public function delete($class, $role, $options=[]): bool
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);

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
        $url_property = $meta->get('Sort.' . $name . '.' . $sort_key . '.' . $url_key);

        $list = $object->data_read($url_property);
        ddd($list);

        d($count);
        ddd($url_property);
        return true;
        /*

        $list = $data->get($name);
        if(empty($list)){
            $list = [];
        }
        $uuid = $node->get('uuid');

        ddd($role);

        foreach($list as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('uuid', $record) &&
                $record['uuid'] === $uuid
            ){
                unset($list[$nr]);
                break;
            }
            elseif(
                is_object($record) &&
                property_exists($record,'uuid') &&
                $record->uuid === $uuid
            ){
                unset($list[$nr]);
                break;
            }
        }
        $result = [];
        foreach($list as $record){
            $result[] = $record;
        }
        $data->set($class, $result);
        $data->write($url);
        $url_node = $dir_node .
            'Storage' .
            $object->config('ds') .
            substr($uuid, 0, 2) .
            $object->config('ds') .
            $uuid .
            $object->config('extension.json')
        ;
        File::delete($url_node);
        return true;
        */
    }
}