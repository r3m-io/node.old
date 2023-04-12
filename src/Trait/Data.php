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

    private function dir(App $object, $dir_node, $dir_data, $dir_uuid, $dir_meta, $dir_validate, $dir_binary_search){
        if(!Dir::is($dir_uuid)){
            Dir::create($dir_uuid, Dir::CHMOD);
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir_uuid;
                exec($command);
                $command = 'chmod 777 ' . $dir_node;
                exec($command);
                $command = 'chmod 777 ' . $dir_data;
                exec($command);
            }
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $dir_uuid;
                exec($command);
                $command = 'chown www-data:www-data ' . $dir_node;
                exec($command);
                $command = 'chown www-data:www-data ' . $dir_data;
                exec($command);
            }
        }
        if(!Dir::is($dir_meta)) {
            Dir::create($dir_meta, Dir::CHMOD);
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir_meta;
                exec($command);
            }
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $dir_meta;
                exec($command);
            }
        }
        if(!Dir::is($dir_validate)) {
            Dir::create($dir_validate, Dir::CHMOD);
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir_validate;
                exec($command);
            }
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $dir_validate;
                exec($command);
            }
        }
        if(!Dir::is($dir_binary_search)) {
            Dir::create($dir_binary_search, Dir::CHMOD);
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir_binary_search;
                exec($command);
                $command = 'chmod 777 ' . Dir::name($dir_binary_search);
                exec($command);
            }
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $dir_binary_search;
                exec($command);
                $command = 'chown www-data:www-data ' . Dir::name($dir_binary_search);
                exec($command);
            }
        }
    }


    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function create($class='', $options=[]): false|array
    {
        $function = __FUNCTION__;
        $name = Controller::name($class);
        $object = $this->object();
        $object->request('node', (object) $options);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_meta = $dir_node .
            'Meta'.
            $object->config('ds')
        ;
        $dir_validate = $dir_node .
            'Validate'.
            $object->config('ds')
        ;
        $dir_binary_search = $dir_node .
            'BinarySearch'.
            $object->config('ds') .
            $name .
            $object->config('ds')
        ;
        $uuid = Core::uuid();
        $dir_data = $dir_node .
            'Storage' .
            $object->config('ds')
        ;
        $dir_uuid = $dir_data .
            substr($uuid, 0, 2) .
            $object->config('ds')
        ;
        $url = $dir_uuid .
            $uuid .
            $object->config('extension.json')
        ;
        if(File::exist($url)){
            return false;
        }
        $this->dir($object,
            $dir_node,
            $dir_data,
            $dir_uuid,
            $dir_meta,
            $dir_validate,
            $dir_binary_search
        );
        $object->request('node.uuid', $uuid);
        $validate_url =
            $dir_validate .
            $name .
            $object->config('extension.json');
        $binary_search_url =
            $dir_binary_search .
            'Data' .
            $object->config('extension.json');
        $meta_url = $dir_meta . $name . $object->config('extension.json');
        $validate = $this->validate($object, $validate_url,  $class . '.create');
        $response = [];
        if($validate) {
            if($validate->success === true) {
                $node = new Storage();
                $node->data($object->request('node'));
                $node->set('#class', $class);

                $binarySearch = $object->data_read($binary_search_url);
                if(!$binarySearch){
                    $binarySearch = new Storage();
                }
                $binarySearch->set($class . '.' . $uuid . '.url' , $url);
                $binarySearch->set($class . '.' . $uuid . '.uuid' , $uuid);
                $list = Sort::list($binarySearch->data($class))->with([
                    'uuid' => 'ASC'
                ]);
                $binarySearch->delete($class);
                $binarySearch->data($class, $list);
                $lines = $binarySearch->write($binary_search_url, 'lines');
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 666 ' . $binary_search_url;
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $binary_search_url;
                    exec($command);
                }
                $meta = $object->data_read($meta_url);
                if(!$meta){
                    $meta = new Storage();
                }
                $meta->set('lines', $lines);
                $count = 0;
                foreach($binarySearch->data($class) as $unused){
                    $count++;
                }
                $meta->set('count', $count);
                $meta->write($meta_url);
                $node->write($url);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 666 ' . $url;
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $url;
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $record = $node->data();
                } else {
                    $expose = $this->getExpose(
                        $object,
                        $class,
                        $class . '.' . $function .'.expose'
                    );
                    ddd($expose);
                    $record = $this->expose(
                        $object,
                        $node->data(),
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
                    'binary_search_url' => $binary_search_url,
                    'meta_url' => $meta_url,
                    'node' => $node->data(),
                ]);
            } else {
                $response['error'] = $validate->test;
                Event::trigger($object, 'r3m.io.node.data.create.error', [
                    'class' => $class,
                    'options' => $options,
                    'url' => $url,
                    'binary_search_url' => $binary_search_url,
                    'meta_url' => $meta_url,
                    'node' => $object->request('node'),
                    'error' => $validate->test,
                ]);
            }
        } else {
            throw new Exception('Cannot validate node at: ' . $validate_url);
        }
        return $response;




        /*
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
        */
    }

    public function read($class='', $options=[]): false|array|object
    {
        $name = Controller::name($class);
        $object = $this->object();
        if(!array_key_exists('uuid', $options)){
            return false;
        }
        $uuid = $options['uuid'];

        $url = $object->config('dir.data') .
            'Node' .
            $object->config('ds') .
            'Storage' .
            $object->config('ds') .
            substr($uuid, 0, 2) .
            $object->config('ds') .
            $uuid .
            $object->config('extension.json')
        ;
        ddd($url);
        if(!File::exist($url)){
            return false;
        }
        return $object->data_read($url, sha1($url));

        /*
        $lines = $meta->get($class . '.' . substr($uuid, 0, 1));
        $seek = (int) (0.5 * $lines);
        $file = new SplFileObject($url);
        $data = [];
        $data = $this->bin_search($file, [
            'uuid' => $uuid,
            'seek' => $seek,
            'lines'=> $lines,
            'counter' => 0,
            'data' => $data,
            'direction' => 'next',
        ]);
        ddd($data);
        return false;
        */
    }

    private function is_uuid($string=''){
        //format: %s%s-%s-%s-%s-%s%s%s
        $explode = explode('-', $string);
        $result = false;
        if(strlen($string) !== 36){
            return $result;
        }
        if(count($explode) !== 5){
            return $result;
        }
        if(strlen($explode[0]) !== 8){
            return $result;
        }
        if(strlen($explode[1]) !== 4){
            return $result;
        }
        if(strlen($explode[2]) !== 4){
            return $result;
        }
        if(strlen($explode[3]) !== 4){
            return $result;
        }
        if(strlen($explode[4]) !== 12){
            return $result;
        }
        return true;
    }

    private function uuid_compare($uuid='', $compare='', $operator='==='){
        $uuid = explode('-', $uuid);
        $compare = explode('-', $compare);
        $result = [];
        foreach($uuid as $nr =>  $hex){
            $dec = hexdec($hex);
            $dec_compare = hexdec($compare[$nr]);
            switch($operator){
                case '===' :
                    if($dec === $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                break;
                case '==' :
                    if($dec == $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                break;
                case '>=' :
                    if($dec === $dec_compare){
                        $result[$nr] = true;
                        break;
                    }
                    if($dec > $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '<=' :
                    if($dec === $dec_compare){
                        $result[$nr] = true;
                        break;
                    }
                    if($dec < $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '>' :
                    if($dec > $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    }
                    elseif($dec === $dec_compare){
                        break;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '<' :
                    if($dec < $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    }
                    elseif($dec === $dec_compare){
                        break;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '!==' :
                    if($dec !== $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                break;
                case '!=' :
                    if($dec != $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                break;
            }
        }
        if(in_array(false, $result, true)){
            return false;
        }
        return true;
    }

    private function uuid_data($file, $options=[]){
        $start = $options['seek'];
        $type = null;
        $data = [];
        while($line = $file->current()){
            $explode = explode(':', $line);
            if(array_key_exists(1, $explode)){
                $value = trim($explode[1]);
            } else {
                $value = trim($explode[0], " \t\n\r\0\x0B,");
                d($value);
            }
            if(
                $type === null &&
                $value === '{'
            ){
                $type = 'object';
                $curly_count = 0;
            }
            elseif(
                $type === null &&
                $value === '['
            ){
                $type = 'array';
            }
            elseif($type === null) {
                $type = 'scalar';
            }
            switch($type){
                case 'object' :
                    if($value === '{'){
                        $curly_count++;
                    }
                    elseif($value === '}'){
                        $curly_count--;
                    }
                    if($curly_count === 0){
                        $data[] = $line;
                        break 2;
                    } else {
                        $data[] = $line;
                    }
                break;
            }
            $file->next();
            $start++;
            if($start > $options['lines']){
                break;
            }
        }
        return $data;
    }

    private function bin_search($file, $options=[]){
        if(!array_key_exists('counter', $options)){
            $options['counter'] = 0;
        }
        if(!array_key_exists('search', $options)){
            $options['search'] = [];
        }
        if(!in_array($options['seek'], $options['search'], true)){
            $options['search'][] = $options['seek'];
        } else {
            return false;
        }
        $file->seek($options['seek']);
        echo 'Status: ' . $options['seek'] . '/' . $options['lines'] . PHP_EOL;
        while($line = $file->current()){
            $options['counter']++;
            if($options['counter'] > 1024){
                break;
            }
            $line_match = str_replace(' ', '', $line);
            $line_match = str_replace('"', '', $line_match);
            $explode = explode(':', $line_match);
            if(array_key_exists(1, $explode)){
                if($this->is_uuid($explode[0])){
                    $uuid_current = $explode[0];
                    if($this->uuid_compare($options['uuid'], $uuid_current, '===')){
                        return $this->uuid_data($file, $options);
                    }
                    elseif($this->uuid_compare($options['uuid'], $uuid_current, '>')){
                        $options['seek'] = (int) (1.5 * $options['seek']);
                        return $this->bin_search($file, $options);
                    }
                    elseif($this->uuid_compare($options['uuid'], $uuid_current, '<')){
                        $options['seek'] = (int) (0.5 * $options['seek']);
                        return $this->bin_search($file, $options);
                    }
                    echo $explode[0] . PHP_EOL;
                }
            }
            $file->next();
        }
    }

    private function binary_search($file, $options=[]){
        $uuid = $options['uuid'];
        $lines = $options['lines'];
        $seek = $options['seek'];
        $data = $options['data'];
        $is_debug = $options['is_debug'] ?? false;
        $counter = $options['counter'] ?? 0;
        $current = $options['current'];
        $direction = $options['direction'] ?? 'next';
        echo 'Lines: ' . $lines . PHP_EOL;
        echo 'Seek: ' . $seek . PHP_EOL;
        while($line = $file->current()){
            $counter++;
            if($counter > 1024){
                break;
            }
//            d($line);
//            d($file->key());
//            echo $current . ' ' . $line . PHP_EOL;
            $line_match = str_replace(' ', '', $line);
            $line_match = str_replace('"', '', $line_match);
            $explode = explode(':', $line_match);
            if(array_key_exists(1, $explode)){
                if($explode[0] === $uuid){
                    d($file->key());
                    d($options);
                    d($current);
                    d($line);
                    ddd('found');
                }
                /*
                if($explode[0] === 'uuid'){
                    if(strpos($explode[1], $uuid) !== false){
                        d($current);
                        ddd($counter);
                        $previous = $current - 1;
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
                */
                if($explode[0] === $uuid){
                    d($file->key());
                    d($options);
                    d($current);
                    d($line);
                    ddd('found');
                }
                d($explode[0]);
                $line_uuid = explode('-', $explode[0]);
                $search_uuid = explode('-', $uuid);
                $is_smaller = false;
                $is_greater = false;
                if(count($line_uuid) === count($search_uuid)){
                    foreach($search_uuid as $nr => $search){
                        $hex = hexdec($search);
                        $match = hexdec($line_uuid[$nr]);
                        if($hex === $match){
                            continue;
                        }
                        elseif($hex < $match){
                            $is_smaller = true;
                            break;
                        }
                        elseif($hex > $match){
                            $is_greater = true;
                            break;
                        }
                    }
                    if($is_smaller){
                        $seek = (int) (0.25 * $lines);
                        $file->seek($seek);
                        $data = $this->binary_search($file, [
                            'uuid' => $uuid,
                            'lines' => $lines,
                            'seek' => $seek,
                            'current' => $seek,
                            'data' => $data,
                            'is_debug' => true,
                            'counter' => $counter,
                            'direction' => 'next',
                        ]);
                    }
                    if($is_greater){
                        $seek = (int) (0.75 * $lines);
                        $file->seek($seek);
                        $data = $this->binary_search($file, [
                            'uuid' => $uuid,
                            'lines' => $lines,
                            'seek' => $seek,
                            'current' => $seek,
                            'data' => $data,
                            'is_debug' => true,
                            'counter' => $counter,
                            'direction' => 'next',
                        ]);
                    }
                }
            }
            if(strpos($line, $uuid . ':') !== false){
                $data[] = $line;
                break;
            }
            switch($direction){
                case 'next':
                    $current++;
                    $file->next();
                    break;
                case 'previous':
                    $current--;
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