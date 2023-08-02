<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Dir;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\File;

use R3m\Io\Exception\ObjectException;

Trait Delete {

    /**
     * @throws ObjectException
     */
    public function delete($class, $role, $options=[]): bool
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $meta_url = $dir_node .
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

        $data = File::read($url_property, File::ARRAY);

        if(!$data){
            return false;
        }
        $uuid = $node->get('uuid');
        if(empty($uuid)){
            return false;
        }
        $is_found = false;
        foreach($data as $nr => $record){
            $record_uuid = rtrim($record, PHP_EOL);
            if($record_uuid === $uuid){
                $is_found = true;
                break;
            }
        }
        /*
        $lines = File::write($url_property, implode('', $data), File::LINES);
        $count = $lines;
        if($count < 0){
            $count = 0;
        }
        $meta->set('Sort.' . $name . '.' . $sort_key . '.' . 'count', $count);
        $meta->set('Sort.' . $name . '.' . $sort_key . '.' . 'lines', $lines);
        $meta->write($meta_url);
        */
        $url_node = $dir_node .
            'Storage' .
            $object->config('ds') .
            substr($uuid, 0, 2) .
            $object->config('ds') .
            $uuid .
            $object->config('extension.json')
        ;
        $target_dir = $object->config('ramdisk.url') .
            $object->config(Config::POSIX_ID) .
            $object->config('ds') .
            'Package' .
            $object->config('ds') .
            'R3m_io' .
            $object->config('ds') .
            'Node' .
            $object->config('ds') .
            'isDeleted' .
            $object->config('ds')
        ;
        Dir::create($target_dir, Dir::CHMOD);
        $target_url = $target_dir .
            $uuid .
            $object->config('extension.json')
        ;
        if(($is_found)){
            return File::move($url_node, $target_url);
        }
        return false;
    }

    /**
     * @throws ObjectException
     */
    public function delete_many($class, $role, $options=[]): array
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $meta_url = $dir_node .
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

        $data = $object->data_read($url_property);
        if(!$data){
            return [];
        }
        $list = (array) $data->get($name);
        if(empty($list)){
            return [];
        }
        $uuids = $node->get('uuid');
        if(empty($uuids) || !is_array($uuids)){
            return [];
        }
        $delete_counter = 0;
        foreach($list as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('uuid', $record) &&
                in_array(
                    $record['uuid'],
                    $uuids,
                    true
                )
            ){
                unset($list[$nr]);
                $delete_counter++;
            }
            elseif(
                is_object($record) &&
                property_exists($record,'uuid') &&
                in_array(
                    $record->uuid,
                    $uuids,
                    true
                )
            ){
                unset($list[$nr]);
                $delete_counter++;
            }
        }
        $index = 0;
        $result = [];
        foreach($list as $record){
            $record->{'#index'} = $index;
            $result[$index] = $record;
            $index++;
        }
        $data->set($name, $result);
        $lines = $data->write($url_property, 'lines');
        $count = $count - $delete_counter;
        $meta->set('Sort.' . $name . '.' . $sort_key . '.' . 'count', $count);
        $meta->set('Sort.' . $name . '.' . $sort_key . '.' . 'lines', $lines);
        $meta->write($meta_url);
        $result = [];
        foreach($uuids as $uuid){
            $url_node = $dir_node .
                'Storage' .
                $object->config('ds') .
                substr($uuid, 0, 2) .
                $object->config('ds') .
                $uuid .
                $object->config('extension.json')
            ;
            $result[$uuid] = File::delete($url_node);
        }
        return $result;
    }
}