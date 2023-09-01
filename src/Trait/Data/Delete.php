<?php

namespace R3m\Io\Node\Trait\Data;

use Exception;
use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Dir;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\File;

use R3m\Io\Exception\DirectoryCreateException;
use R3m\Io\Exception\FileMoveException;
use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Node\Service\Security;

Trait Delete {

    /**
     * @throws ObjectException
     * @throws FileMoveException
     * @throws DirectoryCreateException
     * @throws Exception
     */
    public function delete($class, $role, $options=[]): bool
    {
        if(!array_key_exists('function', $options)){
            $options['function'] = __FUNCTION__;
        }
        $options['relation'] = false;
        if(!Security::is_granted(
            $class,
            $role,
            $options
        )){
            return false;
        }
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
            throw new Exception('No data found in: ' . $url_property);
        }
        $uuid = $node->get('uuid');
        if(empty($uuid)){
            throw new Exception('No uuid set.');
        }
        $is_found = false;
        foreach($data as $nr => $record){
            $record_uuid = rtrim($record, PHP_EOL);
            if($record_uuid === $uuid){
                $is_found = true;
                break;
            }
        }
        /**
         * if we delete them from the list a sync is needed
         * so we only move the file and reading will skip it
         */
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
            $data = $object->data_read($url_node);
            if(
                $data &&
                $data->has('#class') &&
                $data->get('#class') === $name
            ){
                return File::move($url_node, $target_url);
            }
            if(!$data){
                return File::move($url_node, $target_url);
            }
        }
        throw new Exception('No data found in: ' . $url_node);
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws DirectoryCreateException
     * @throws FileMoveException
     * @throws Exception
     */
    public function delete_many($class, $role, $options=[]): array
    {
        if(!array_key_exists('function', $options)){
            $options['function'] = __FUNCTION__;
        }
        $options['relation'] = false;
        if(!Security::is_granted(
            $class,
            $role,
            $options
        )){
            return [];
        }
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

        $list = File::read($url_property, File::ARRAY);
        $uuids = $node->get('uuid');
        if(empty($uuids) || !is_array($uuids)){
            return [];
        }
        /**
         * if we delete them from the list a sync is needed
         * so we only move the file and reading will skip it
         */
        /*
        $delete_counter = 0;
        foreach($list as $nr => $line){
            $uuid = rtrim($line, PHP_EOL);
            if(in_array($uuid, $uuids, true)){
                unset($list[$nr]);
                $delete_counter++;
            }
        }
        $lines = File::write($url_property, implode('', $list), File::LINES);
        $count = $count - $delete_counter;
        $meta->set('Sort.' . $name . '.' . $sort_key . '.' . 'count', $count);
        $meta->set('Sort.' . $name . '.' . $sort_key . '.' . 'lines', $lines);
        $meta->write($meta_url);
        */
        $result = [];
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
        foreach($uuids as $uuid){
            $url_node = $dir_node .
                'Storage' .
                $object->config('ds') .
                substr($uuid, 0, 2) .
                $object->config('ds') .
                $uuid .
                $object->config('extension.json')
            ;
            $target_url = $target_dir .
                $uuid .
                $object->config('extension.json')
            ;
            $data = $object->data_read($url_node);
            if(
                $data &&
                $data->has('#class') &&
                $data->get('#class') === $name
            ){
                $result[$uuid] = File::move($url_node, $target_url);
            }
            if(!$data){
                $result[$uuid] = File::move($url_node, $target_url);
            }
        }
        return $result;
    }
}