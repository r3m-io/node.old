<?php

namespace R3m\Io\Node\Trait;

use Exception;
use R3m\Io\App;
use R3m\Io\Config;
use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use SplFileObject;
use stdClass;

Trait BinarySearch {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    private function binary_search_list_create(App $object, $class, $options=[]): void
    {
        $name = Controller::name($class);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds');
        $dir_binarysearch = $dir_node .
            'BinarySearch' .
            $object->config('ds') .
            $name .
            $object->config('ds')
        ;
        $url = $dir_binarysearch .
            'Asc' .
            $object->config('ds') .
            'Uuid' .
            $object->config('extension.json')
        ;
        $meta_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $data = $object->data_read($url);
        if(!$data){
            return;
        }
        $meta = $object->data_read($meta_url, sha1($meta_url));
        if(!$meta){
            return;
        }
        $object_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $object_class = $object->data_read($object_url);
        $list = new Storage();
        $mtime = File::mtime($url);

        $properties = [];

        $url_key = 'url.';

        foreach($options['sort'] as $key => $order) {
            if(empty($properties)){
                $url_key .= 'asc.';
            } else {
                $url_key .= strtolower($order) . '.';
            }
            $properties[] = $key;
        }
        $url_key = substr($url_key, 0, -1);
        $sort_key = sha1(Core::object($properties, Core::OBJECT_JSON));
        $url_property = $meta->get('Sort.' . $class . '.' . $sort_key . '.'. $url_key);
        $sort_lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
        if(!empty($options['filter'])){
            $key = [
                'filter' => $options['filter'],
                'sort' => $options['sort']
            ];
            $key = sha1(Core::object($key, Core::OBJECT_JSON));
            $file = new SplFileObject($url_property);
            $limit = $meta->get('Filter.' . $class . '.' . $key . '.limit') ?? 1000;
            $filter_list = $this->binary_search_list($file, [
                'filter' => $options['filter'],
                'limit' => $limit,
                'lines'=> $sort_lines,
                'counter' => 0,
                'direction' => 'next',
                'url' => $url_property,
            ]);
            if(!empty($filter_list)){
                $filter = [];
                foreach($filter_list as $index => $node){
                    $filter[$key][$index] = [
                        'uuid' => $node->uuid,
                        '#index' => $index,
                        '#key' => $key
                    ];
                }
                $filter_dir = $dir_node .
                    'Filter' .
                    $object->config('ds')
                ;
                $filter_name_dir = $filter_dir .
                    $name .
                    $object->config('ds')
                ;
                Dir::create($filter_name_dir, Dir::CHMOD);
                $filter_url = $filter_name_dir .
                    $key .
                    $object->config('extension.json')
                ;
                $storage = new Storage($filter);
                $lines = $storage->write($filter_url, 'lines');
                File::touch($filter_url, $mtime);
                $count = $index + 1;
                $meta->set('Filter.' . $class . '.' . $key . '.lines', $lines);
                $meta->set('Filter.' . $class . '.' . $key . '.count', $count);
                $meta->set('Filter.' . $class . '.' . $key . '.limit', $limit);
                $meta->set('Filter.' . $class . '.' . $key . '.filter', $options['filter']);
                $meta->set('Filter.' . $class . '.' . $key . '.sort', $options['sort']);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $filter_url;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $filter_dir;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $filter_name_dir;
                    exec($command);
                }
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                    $command = 'chmod 666 ' . $filter_url;
                    exec($command);
                    $command = 'chmod 777 ' . $filter_dir;
                    exec($command);
                    $command = 'chmod 777 ' . $filter_name_dir;
                    exec($command);
                }
            }
        }
        elseif(!empty($options['where'])){
            $options['where'] = $this->where_convert($options['where']);
            $key = [
                'where' => $options['where'],
                'sort' => $options['sort']
            ];
            $key = sha1(Core::object($key, Core::OBJECT_JSON));
            $file = new SplFileObject($url_property);
            $limit = $meta->get('Where.' . $class . '.' . $key . '.limit') ?? 1000;
            $where_list = $this->binary_search_list($file, [
                'where' => $options['where'],
                'limit' => $limit,
                'lines'=> $sort_lines,
                'counter' => 0,
                'direction' => 'next',
                'url' => $url_property,
            ]);
            if(!empty($where_list)){
                $where = [];
                foreach($where_list as $index => $node){
                    $where[$key][$index] = [
                        'uuid' => $node->uuid,
                        '#index' => $index,
                        '#key' => $key
                    ];
                }
                $where_dir = $dir_node .
                    'Where' .
                    $object->config('ds')
                ;
                $where_name_dir = $where_dir .
                    $name .
                    $object->config('ds')
                ;
                Dir::create($where_name_dir, Dir::CHMOD);
                $where_url = $where_name_dir .
                    $key .
                    $object->config('extension.json')
                ;
                $storage = new Storage($where);
                $lines = $storage->write($where_url, 'lines');
                File::touch($where_url, $mtime);
                $count = $index + 1;
                $meta->set('Where.' . $class . '.' . $key . '.lines', $lines);
                $meta->set('Where.' . $class . '.' . $key . '.count', $count);
                $meta->set('Where.' . $class . '.' . $key . '.limit', $limit);
                $meta->set('Where.' . $class . '.' . $key . '.where', $options['where']);
                $meta->set('Where.' . $class . '.' . $key . '.sort', $options['sort']);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $where_url;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $where_dir;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $where_name_dir;
                    exec($command);
                }
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                    $command = 'chmod 666 ' . $where_url;
                    exec($command);
                    $command = 'chmod 777 ' . $where_dir;
                    exec($command);
                    $command = 'chmod 777 ' . $where_name_dir;
                    exec($command);
                }
            }
        }
        $meta->write($meta_url);
        if($object->config(Config::POSIX_ID) === 0){
            $command = 'chown www-data:www-data ' . $meta_url;
            exec($command);
        }
        if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
            $command = 'chmod 666 ' . $meta_url;
            exec($command);
        }
    }

    /**
     * @throws Exception
     */
    private function binary_search_page($file, $options=[]): array
    {
        $object = $this->object();
        $index = 0;
        if(
            array_key_exists('page', $options) &&
            array_key_exists('limit', $options)
        ){
            $index = ($options['page'] * $options['limit']) - $options['limit'];
        }
        $start = $index;
        $end = $start + $options['limit'];
        $page = [];
        $time_start = microtime(true);
        $record_index = $index;
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_search_index($file, [
                'page' => $options['page'],
                'limit' => $options['limit'],
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
                'url' => $options['url'],
            ]);
            ddd($record);
            if($record){
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                if(!empty($options['filter'])){
                    $record = $this->filter($record, $options['filter'], $options);
                }
                elseif(!empty($options['where'])){
                    $record = $this->where($record, $options['where'], $options);
                }
                if($record){
                    $record->{'#index'} = $record_index;
                    $page[] = $record;
                    $record_index++;
                } else {
                    $end++;
                }
            } else {
                break;
            }
        }
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        if($duration < 1) {
            echo 'Duration: ' . round($duration * 1000, 2) . ' msec' . PHP_EOL;
        } else {
            echo 'Duration: ' . round($duration, 2) . ' sec' . PHP_EOL;
        }
        return $page;
    }

    /**
     * @throws Exception
     */
    private function binary_search_one($file, $options=[]): array
    {
        $object = $this->object();
        $index = 0;
        $options['page'] = 1;
        $options['limit'] = 1;
        $start = $index;
        $end = $start + $options['limit'];
        $page = [];
        $time_start = microtime(true);
        $record_index = $index;
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_search_index($file, [
                'page' => $options['page'],
                'limit' => $options['limit'],
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
                'url' => $options['url'],
            ]);
            if($record){
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                if(!empty($options['filter'])){
                    $record = $this->filter($record, $options['filter'], $options);
                }
                elseif(!empty($options['where'])){
                    $record = $this->where($record, $options['where'], $options);
                }
                if($record){
                    $record->{'#index'} = $record_index;
                    $page[] = $record;
                    $record_index++;
                } else {
                    $end++;
                }
            } else {
                break;
            }
        }
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        if($duration < 1) {
            echo 'Duration: ' . round($duration * 1000, 2) . ' msec' . PHP_EOL;
        } else {
            echo 'Duration: ' . round($duration, 2) . ' sec' . PHP_EOL;
        }
        return $page;
    }

    /**
     * @throws Exception
     */
    private function binary_search_list($file, $options=[]): array
    {
        if(!array_key_exists('limit', $options)){
            return [];
        }
        if(!array_key_exists('lines', $options)){
            return [];
        }
        $object = $this->object();
        $index = 0;
        $start = $index;
        $end = $start + (int) $options['limit'];
        $page = [];
        $record_index = $index;
        $time_start = microtime(true);
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_search_index($file, [
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
                'url' => $options['url'],
            ]);
            if($record){
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                //add expose
                if(!empty($options['filter'])){
                    $record = $this->filter($record, $options['filter'], $options);
                }
                elseif(!empty($options['where'])){
                    $record = $this->where($record, $options['where'], $options);
                }
                if($record){
                    $record->{'#index'} = $record_index;
                    $page[] = $record;
                    $record_index++;
                } else {
                    $end++;
                }
            } else {
                break;
            }
        }
        $time_end = microtime(true);
        $duration = $time_end - $time_start;
        if($duration < 1) {
            echo 'Duration: ' . round($duration * 1000, 2) . ' msec' . PHP_EOL;
        } else {
            echo 'Duration: ' . round($duration, 2) . ' sec' . PHP_EOL;
        }
        return $page;
    }

    private function parse_index($data=[]): false|int
    {
        foreach($data as $nr => $line){
            if(strpos($line, '#index') !== false){
                $line = str_replace('"#index"', '', $line);
                $line = trim($line, " :,\n");
                return (int) $line;
            }
        }
        return false;
    }

    /**
     * @throws ObjectException
     */
    private function binary_search_node($data=[], $options=[]){
        if(!is_array($data)){
            return false;
        }
        foreach($data as $nr => $line){
            $data[$nr] = ltrim($line);
        }
        $data = implode('', $data);
        $record  = Core::object($data, Core::OBJECT_OBJECT);
        if(!is_object($record)){
            return false;
        }
        if(!property_exists($record, 'uuid')){
            return false;
        }
        if(!array_key_exists('counter', $options)){
            return false;
        }
        if(!array_key_exists('seek', $options)){
            return false;
        }
        if(!array_key_exists('lines', $options)){
            return false;
        }
        $record->{'#read'} = new stdClass();
        $record->{'#read'}->load = $options['counter'];
        $record->{'#read'}->seek = $options['seek'];
        $record->{'#read'}->lines = $options['lines'];
        $record->{'#read'}->percentage = round(($options['counter'] / $options['lines']) * 100, 2);
        $object = $this->object();
        $record->{'#read'}->url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Storage' .
            $object->config('ds') .
            substr($record->uuid, 0, 2) .
            $object->config('ds') .
            $record->uuid .
            $object->config('extension.json')
        ;
        return $record;
    }

    /**
     * @throws ObjectException
     */
    private function binary_search_index($file, $options=[]){
        $object = $this->object();
        if(!array_key_exists('counter', $options)){
            $options['counter'] = 0;
        }
        if(!array_key_exists('lines', $options)){
            return false;
        }
        if(!array_key_exists('index', $options)){
            return false;
        }
        if(!array_key_exists('search', $options)){
            return false;
        }
        if(!array_key_exists('min', $options)){
            $options['min'] = 0;
        }
        if(!array_key_exists('max', $options)){
            $options['max'] = $options['lines'] - 1;
        }
        $direction = 'up';
        while($options['min'] <= $options['max']){
            $seek = $options['min'] + floor(($options['max'] - $options['min']) / 2);
            if(
                $direction === 'down' &&
                !in_array($seek, $options['search'], true)
            ){
                $options['search'][] = $seek;
            }
            elseif($direction === 'down') {
                //not found
                return false;
            }
            $file->seek($seek);
            $depth = false;
            $is_collect = false;
            $data = [];
            while($line = $file->current()){
                $options['counter']++;
                if($options['counter'] > 1024){
                    //log error with filesize of view
                    break 2;
                }
                $line_match = str_replace(' ', '', $line);
                $line_match = str_replace('"', '', $line_match);
                $explode = explode(':', $line_match);
                $index = false;
//                echo $seek . ', ' . $direction . ', ' . $line . PHP_EOL;
                $symbol = trim($explode[0], " \t\n\r\0\x0B,");
                $symbol_right = null;
                if(array_key_exists(1, $explode)){
                    $symbol_right = trim($explode[1], " \t\n\r\0\x0B,");
                }
                if($symbol === '{'){
                    $depth = 0;
                    $direction = 'down';
                    $is_collect = true;
                }
                if(
                    $depth !== false &&
                    $symbol === '}' ||
                    $symbol_right === '}'
                ){
//                    echo $symbol . '-' . $symbol_right . '-' . $depth . PHP_EOL;
                    $depth--;
                    if($depth === 0){
                        $data[] = $symbol;
                        $index = $this->parse_index($data);
                        if($index === false){
                            $object->logger($object->config('project.log.name'))->error('Cannot find index in view: ' . $options['url'], $data);
                        }
                        if ($options['index'] === $index) {
                            return $this->binary_search_node($data, [
                                'seek' => $seek,
                                ...$options
                            ]);
                        }
                        elseif(
                            $options['index'] < $index
                        ){
                            $direction = 'up';
                            $options['max'] = $seek - 1;
                            break;
                        }
                        elseif(
                            $options['index'] > $index
                        ){
                            if(in_array($seek, $options['search'], true)){
                                $direction = 'down';
                            } else {
                                $direction = 'up';
                            }
                            $options['min'] = $seek + 1;
                            break;
                        }
                    }
                }
                elseif(
                    $depth !== false &&
                    $symbol === '{' ||
                    $symbol_right === '{'
                ){
                    $depth++;
//                    echo $symbol . '-' . $symbol_right . '-' . $depth . PHP_EOL;
                }
                if($is_collect){
                    $data[]= $line;
                }
                if($direction === 'up'){
                    $seek--;
                    if($seek < 0){
                        $direction = 'down';
                        $seek = 0;
                    }
                    $file->seek($seek);
                    $options['search'][] = $seek;
                } else {
                    $seek++;
                    $options['search'][] = $seek;
                    $file->next();
                    if($seek === $options['lines'] - 1){
                        $direction = 'up';
                    }
                }
            }
        }
        return false;
    }
}