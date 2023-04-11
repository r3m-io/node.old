<?php

namespace R3m\Io\Node\Trait;

use SplFileObject;
use stdClass;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\Event;
use R3m\Io\Module\File;
use R3m\Io\Module\Limit;
use R3m\Io\Module\Sort;
use R3m\Io\Module\Validate;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Data {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function create($class='', $options=[], $as_void=false): null|false|array
    {
        $function = __FUNCTION__;
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $uuid = Core::uuid();
        $dir_data = $dir_class .
            'Data' .
            $object->config('ds')
        ;
        $dir_uuid = $dir_data .
            substr($uuid, 0, 1) .
            $object->config('ds')
        ;
        $url = $dir_uuid .
            'Data' .
            $object->config('extension.json')
        ;
        $data = $object->data_read($url, sha1($url));
        if(!$data){
            $data = new Storage();
            Dir::create($dir_uuid, Dir::CHMOD);
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 777 ' . $dir_uuid;
                exec($command);
                $command = 'chmod 777 ' . $dir_data;
                exec($command);
                $command = 'chmod 777 ' . $dir_node;
                exec($command);
                $command = 'chmod 777 ' . $dir_class;
                exec($command);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir_uuid . ' -R';
                    exec($command);
                    $command = 'chown www-data:www-data ' . $dir_data;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $dir_class;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $dir_node;
                    exec($command);
                }
            }
        }
        $node->set('uuid', $uuid);
        $object->request('node', $node->data());

        $validate_url =  $dir_class . 'Validate.json';
        $validate = $this->validate($object, $validate_url,  $class . '.create');
        $response = [];
        if($validate) {
            if($validate->success === true) {
                $data->set($class . '.' . $uuid, $object->request('node'));

                $list = Sort::list($data->data($class))->with([
                    'uuid' => 'ASC'
                ]);
                $data->delete($class);
                $data->data($class, $list);
                $lines = $data->write($url, 'lines');
                $meta_url = $dir_data . 'Meta.json';
                $meta = $object->data_read($meta_url, sha1($meta_url));
                if(!$meta){
                    $meta = new Storage();
                }
                $meta->set($class . '.' . substr($uuid, 0, 1), $lines);
                $meta->write($meta_url);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                    $command = 'chmod 666 ' . $url;
                    exec($command);
                    $command = 'chmod 666 ' . $meta_url;
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $url;
                    exec($command);
                    $command = 'chown www-data:www-data ' . $meta_url;
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $record = $object->request('node');
                } else {
                    $expose = $this->getExpose(
                        $object,
                        $class,
                        $class . '.' . $function .'.expose'
                    );
                    ddd($expose);
                    $record = $this->expose(
                        $object,
                        $object->request('node'),
                        $expose,
                        $class,
                        $function
                    );
                }
                $response['node'] = $record;
                Event::trigger($object, 'r3m.io.node.data.create', [
                    'class' => $class,
                    'options' => $options,
                    'url' => $url,
                    'node' => $node->data(),
                ]);
            } else {
                $response['error'] = $validate->test;
                Event::trigger($object, 'r3m.io.node.data.create.error', [
                    'class' => $class,
                    'options' => $options,
                    'url' => $url,
                    'node' => $node->data(),
                    'error' => $validate->test,
                    'as_void' => $as_void,
                ]);
            }
            if($as_void === false){
                return $response;
            } else {
                return null;
            }
        } else {
            throw new Exception('Cannot validate node at: ' . $validate_url);
        }
        if($as_void === false){
            return false;
        }
        return null;
    }

    public function read($class='', $options=[]): false|array|object
    {
        $name = Controller::name($class);
        $object = $this->object();
        if(!array_key_exists('uuid', $options)){
            return false;
        }
        $uuid = $options['uuid'];
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $dir_data = $dir_class .
            'Data' .
            $object->config('ds')
        ;
        $dir_uuid = $dir_data .
            substr($uuid, 0, 1) .
            $object->config('ds')
        ;
        $url = $dir_uuid .
            'Data' .
            $object->config('extension.json')
        ;
        $meta_url = $dir_data . 'Meta.json';
        $meta = $object->data_read($meta_url, sha1($meta_url));
        if(!$meta){
            return false;
        }
        $lines = $meta->get($class . '.' . substr($uuid, 0, 1));
        $seek = (int) (0.5 * $lines);
        $file = new SplFileObject($url);
        $file->seek($seek);
        $data = [];
        $data = $this->binary_search($file, [
            'uuid' => $uuid,
            'seek' => $seek,
            'lines'=> $lines,
            'data' => $data,
            'direction' => 'next',
        ]);
        ddd($data);
        return false;
    }

    private function binary_search($file, $options=[]){
        $uuid = $options['uuid'];
        $lines = $options['lines'];
        $seek = $options['seek'];
        $data = $options['data'];
        $is_debug = $options['is_debug'] ?? false;
        $counter = $options['counter'] ?? 0;
        $current = $lines;
        $direction = $options['direction'] ?? 'next';
        while($line = $file->current()){
            $counter++;
            if($counter > 1024){
                break;
            }
//            d($line);
//            d($file->key());
            echo $current . ' ' . $line . PHP_EOL;
            $line_match = str_replace(' ', '', $line);
            $line_match = str_replace('"', '', $line_match);
            $explode = explode(':', $line_match);
            if(array_key_exists(1, $explode)){
                if($explode[0] === 'uuid'){
                    if(strpos($explode[1], $uuid) !== false){
                        ddd($counter);
                        $previous = $file->key() - 1;
                        if($previous < 0){
                            break;
                        }
                        $file->seek($previous);
                        $tmp = [];
                        while($previous > 0){
                            $line = $file->current();
                            $tmp[] = $line;
                            $previous = $file->key() - 1;
                            if($previous < 0){
                                break;
                            }
                            $file->seek($previous);
                        }
                        ddd($tmp);
                    }
                }
                if($explode[0] === $uuid){
                    d($file->key());
                    d($options);
                    d($line);
                    ddd('found');
                }
                d($explode[0]);
                $line_uuid = explode('-', $explode[0]);
                $search_uuid = explode('-', $uuid);
                if(count($line_uuid) === count($search_uuid)){
                    foreach($search_uuid as $nr => $search){
                        $hex = hexdec($search);
                        $match = hexdec($line_uuid[$nr]);
                        if($hex === $match){
                            continue;
                        }
                        elseif($hex < $match){
                            $seek = (int) (0.25 * $lines);
                            $file->fseek($seek);
                            $data = $this->binary_search($file, [
                                'uuid' => $uuid,
                                'lines' => $lines,
                                'seek' => $seek,
                                'data' => $data,
                                'is_debug' => true,
                                'counter' => $counter,
                                'direction' => 'next',
                            ]);
                        }
                        elseif($hex > $match){
                            $seek = (int) (0.75 * $lines);
                            $file->fseek($seek);
                            $data = $this->binary_search($file, [
                                'uuid' => $uuid,
                                'lines' => $lines,
                                'seek' => $seek,
                                'data' => $data,
                                'is_debug' => true,
                                'counter' => $counter,
                                'direction' => 'next',
                            ]);
                        }
                    }
                }
            }
            if(strpos($line, $uuid . ':') !== false){
                $data[] = $line;
                break;
            }
            switch($direction){
                case 'next':
                    $file->next();
                    break;
                case 'previous':
                    $previous = $file->key() - 1;
                    if($previous < 0){
                        break 2;
                    }
                    $file->seek($previous);
                    break;
            }
        }
        return $data;
    }

    public function patch($class, $options=[]): false|array|object
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $url = $dir_class . 'Data.json';
        $data = $object->data_read($url);
        if(!$data){
            return false;
        }
        $list = $data->get($class);
        if(empty($list)){
            $list = [];
        }
        $uuid = $node->get('uuid');
        $is_found = false;
        $record = false;
        foreach($list as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('uuid', $record) &&
                $record['uuid'] === $uuid
            ){
                foreach($node->data() as $attribute => $value){
                    if($attribute === 'uuid'){
                        continue;
                    }
                    $list[$nr][$attribute] = $value;
                }
                $is_found = true;
                $record = $list[$nr];
                break;
            }
            elseif(
                is_object($record) &&
                property_exists($record,'uuid') &&
                $record->uuid === $uuid
            ){
                foreach($node->data() as $attribute => $value){
                    if($attribute === 'uuid'){
                        continue;
                    }
                    $record->{$attribute} = $value;
                }
                $is_found = true;
                $record = $list[$nr];
                break;
            }
        }
        if($is_found){
            $data->set($class, $list);
            $data->write($url);
            return $record;
        }
        return false;
    }

    public function put($class, $options=[]): false|array|object{
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $url = $dir_class . 'Data.json';
        $data = $object->data_read($url);
        if(!$data){
            return false;
        }
        $list = $data->get($class);
        if(empty($list)){
            $list = [];
        }
        $uuid = $node->get('uuid');
        $is_found = false;
        $record = false;
        foreach($list as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('uuid', $record) &&
                $record['uuid'] === $uuid
            ){
                $list[$nr] = [];
                foreach($node->data() as $attribute => $value){
                    $list[$nr][$attribute] = $value;
                }
                $record = $list[$nr];
                $is_found = true;
                break;
            }
            elseif(
                is_object($record) &&
                property_exists($record,'uuid') &&
                $record->uuid === $uuid
            ){
                $list[$nr] = new stdClass();
                foreach($node->data() as $attribute => $value){
                    $list[$nr]->{$attribute} = $value;
                }
                $record = $list[$nr];
                $is_found = true;
                break;
            }
        }
        if($is_found){
            $data->set($class, $list);
            $data->write($url);
            return $record;
        }
        return false;
    }

    public function delete($class, $options=[]): bool
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $url = $dir_class . 'Data.json';
        $data = $object->data_read($url);
        if(!$data){
            return false;
        }
        $list = $data->get($class);
        if(empty($list)){
            $list = [];
        }
        $uuid = $node->get('uuid');
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
        return true;
    }


    /**
     * @throws ObjectException
     */
    public function list($class='', $options=[]): false|array
    {
        $options = Core::object($options, Core::OBJECT_ARRAY);
        $function = __FUNCTION__;
        $name = Controller::name($class);
        $object = $this->object();
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds');
        $dir_class = $dir_node .
            $name .
            $object->config('ds');
        $url = $dir_class . 'Data.json';
        $data = $object->data_read($url);
        if (!$data) {
            return false;
        }
        $list = $data->get($class);
        if (empty($list)) {
            $list = [];
        }
        $response = [];
        if(array_key_exists('order', $options)){
            $list = Sort::list($list)->with($options['order'], true);
        }
        if(
            array_key_exists('limit', $options) &&
            array_key_exists('page', $options)
        ){
            $list = Limit::list($list)->with([
                'limit' => $options['limit'],
                'page' => $options['page'],
            ]);
        }
        $response['list'] = $list;
        $response['limit'] = $options['limit'] ?? 0;
        $response['page'] = $options['page'] ?? 1;
        $response['order'] = $options['order'] ?? [];
        $response['filter'] = $options['filter'] ?? [];
        Event::trigger($object, 'r3m.io.node.data.list', [
            'class' => $class,
            'options' => $options,
            'url' => $url,
            'list' => $list,
        ]);
        return $response;
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

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    protected function validate(App $object, $url, $type){
        $data = $object->data(sha1($url));
        if($data === null){
            $data = $object->parse_read($url, sha1($url));
        }
        if($data){
            $validate = $data->data($type . '.validate');
            if(empty($validate)){
                return false;
            }
            return Validate::validate($object, $validate);
        }
        return false;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    protected function expose(App $object, Storage $node, $expose, $class, $function){

        $record = $node->data();
        return $record;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    protected function getExpose(App $object, $name='', $attribute=''){
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $url = $dir_class . 'Expose.json';
        if(!File::exist($url)){
            throw new Exception('Data url (' . $url . ') not found for class: ' . $name);
        }
        $data = $object->data_read($url);
        if($data){
            $get = $data->get($attribute);
            if(empty($get)){
                throw new Exception('Cannot find attribute (' . $attribute .') in class: ' . $name);
            }
            return $get;
        }
    }
}