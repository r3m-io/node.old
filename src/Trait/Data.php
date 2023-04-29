<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\Module\Filter;
use R3m\Io\Module\Parse;
use R3m\Io\Module\Parse\Token;
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
            $object->config('ds')
        ;
        $dir_binary_search_class = $dir_binary_search .
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
        $dir_binary_search =
            $dir_binary_search_class .
            'Asc' .
            $object->config('ds')
        ;
        $this->dir($object,
            [
                'node' => $dir_node,
                'uuid' => $dir_uuid,
                'meta' => $dir_meta,
                'validate' => $dir_validate,
                'binary_search_class' => $dir_binary_search_class,
                'binary_search' => $dir_binary_search,
            ]
        );
        $object->request('node.uuid', $uuid);
        $validate_url =
            $dir_validate .
            $name .
            $object->config('extension.json');

        $binary_search_url =
            $dir_binary_search .
            'Uuid' .
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
//                $binarySearch->set($class . '.' . $uuid . '.url', $url);
                $binarySearch->set($class . '.' . $uuid . '.uuid', $uuid);
                $list = Sort::list($binarySearch->data($class))->with([
                    'uuid' => 'ASC'
                ]);
                $binarySearch->delete($class);
                $binarySearch->data($class, $list);
                $count = 0;
                foreach($binarySearch->data($class) as $record){
                    $record->{'#index'} = $count;
                    $count++;
                }
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
                $property = [];
                $property[] = 'uuid';

                $key = sha1(Core::object($property, Core::OBJECT_JSON));

                $meta->set('Sort.' . $class . '.' . $key . '.property', $property);
                $meta->set('Sort.' . $class . '.' . $key . '.lines', $lines);
                $meta->set('Sort.' . $class . '.' . $key . '.count', $count);
                $meta->set('Sort.' . $class . '.' . $key . '.url.asc', $binary_search_url);
                $meta->write($meta_url);
                $node->write($url);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
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
    }

    public function file_create_many($options=[]){
        $directory = false;
        if(array_key_exists('directory', $options)){
            $directory = $options['directory'];
        }
        if(empty($directory)){
            return false;
        }
        if(array_key_exists('recursive', $options)){
            $recursive = $options['recursive'];
        } else {
            $recursive = false;
        }
        $dir = new Dir();
        $files = $dir->read($directory, $recursive);
        foreach($files as $file){
            $file->extension = File::extension($file->url);
            switch($file->extension){
                case 'php':
                    $file->read = explode(PHP_EOL, File::read($file->url));
//                    $file->class = Php::false;


                    /*
                     * #class
                     * #namespace
                     * #trait
                     * #function
                     * #controller
                     */
                break;
                case 'tpl':
                    /*
                     * #module
                     * #submodule
                     * #command
                     * #subcommand
                     * #controller
                     */
                break;
                case 'js':
                    /*
                     * #module
                     * #prototype
                     */
                break;
                case 'json':
                    /*
                     * #function
                     * #controller
                     */
                break;
            }
        }
        ddd($files);
    }

    public function read($class='', $options=[]): false|array|object
    {
        $name = Controller::name($class);
        $object = $this->object();
        if(!array_key_exists('uuid', $options)){
            return false;
        }
        $uuid = $options['uuid'];

        $url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Storage' .
            $object->config('ds') .
            substr($uuid, 0, 2) .
            $object->config('ds') .
            $uuid .
            $object->config('extension.json')
        ;
        if(!File::exist($url)){
            return false;
        }
        $data = $object->data_read($url, sha1($url));
        if(!$data){
            return false;
        }
        return $data->data();
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
     * @throws FileWriteException
     * @throws Exception
     */
    public function list($class='', $options=[]): false|array
    {
        $name = Controller::name($class);
        $options = Core::object($options, Core::OBJECT_ARRAY);
        $function = __FUNCTION__;
        $object = $this->object();
        $this->binary_search_list_create($object, $class, $options);
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
            if(!File::exist($url)){
                return false;
            }
            $mtime = File::mtime($url);
            $list = [];
            if(!$has_descending){
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
                if(!empty($options['filter'])){
                    $key = [
                        'filter' => $options['filter'],
                        'sort' => $options['sort']
                    ];
                    $key = sha1(Core::object($key, Core::OBJECT_JSON));
                    $lines = $meta->get('Filter.' . $name . '.' . $key . '.lines');
                    $filter_url = $object->config('project.dir.data') .
                        'Node' .
                        $object->config('ds') .
                        'Filter' .
                        $object->config('ds') .
                        $name .
                        $object->config('ds') .
                        $key .
                        $object->config('extension.json')
                    ;
                    $filter_mtime = File::mtime($filter_url);
                    if(
                        File::exist($filter_url) &&
                        $mtime === $filter_mtime &&
                        $lines >= 0
                    ){
                        $file = new SplFileObject($filter_url);
                        $options['filter']['#key'] = $key;
                        $list = $this->binary_search_page($file, [
                            'filter' => $options['filter'],
                            'page' => $options['page'],
                            'limit' => $options['limit'],
                            'lines'=> $lines,
                            'counter' => 0,
                            'direction' => 'next',
                            'url' => $filter_url,
                            'debug' => true
                        ]);
                    } else {
                        $sort_key = sha1(Core::object($properties, Core::OBJECT_JSON));
                        $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                        if(
                            File::exist($url) &&
                            $lines > 0
                        ){
                            $file = new SplFileObject($url);
                            $list = $this->binary_search_page($file, [
                                'filter' => $options['filter'],
                                'page' => $options['page'],
                                'limit' => $options['limit'],
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url
                            ]);
                        }

                    }
                    $result = [];
                    $result['page'] = $options['page'];
                    $result['limit'] = $options['limit'];
                    $result['list'] = $list;
                    $result['sort'] = $options['sort'];
                    $result['filter'] = $options['filter'] ?? [];
                    return $result;
                }
                elseif(!empty($options['where'])){
                    $options['where'] = $this->where_convert($options['where']);
                    $key = [
                        'where' => $options['where'],
                        'sort' => $options['sort']
                    ];
                    $key = sha1(Core::object($key, Core::OBJECT_JSON));
                    $lines = $meta->get('Where.' . $name . '.' . $key . '.lines');
                    $where_url = $object->config('project.dir.data') .
                        'Node' .
                        $object->config('ds') .
                        'Where' .
                        $object->config('ds') .
                        $name .
                        $object->config('ds') .
                        $key .
                        $object->config('extension.json')
                    ;
                    $where_mtime = File::mtime($where_url);
                    if(
                        File::exist($where_url) &&
                        $mtime === $where_mtime &&
                        $lines >= 0
                    ){
                        $file = new SplFileObject($where_url);
                        $where = [];
                        $where[] = [
                            'value' => $key,
                            'attribute' => 'key',
                            'operator' => '==='
                        ];
                        $list = $this->binary_search_page($file, [
                            'where' => $where,
                            'page' => $options['page'],
                            'limit' => $options['limit'],
                            'lines'=> $lines,
                            'counter' => 0,
                            'direction' => 'next',
                            'url' => $where_url,
                            'debug' => true
                        ]);
                    } else {
                        $sort_key = sha1(Core::object($properties, Core::OBJECT_JSON));
                        $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
                        if(
                            File::exist($url) &&
                            $lines > 0
                        ){
                            $file = new SplFileObject($url);
                            $list = $this->binary_search_page($file, [
                                'where' => $options['where'],
                                'page' => $options['page'],
                                'limit' => $options['limit'],
                                'lines'=> $lines,
                                'counter' => 0,
                                'direction' => 'next',
                                'url' => $url
                            ]);
                        }
                    }
                    $result = [];
                    $result['page'] = $options['page'];
                    $result['limit'] = $options['limit'];
                    $result['list'] = $list;
                    $result['sort'] = $options['sort'];
                    $result['where'] = $options['where'] ?? [];
                    return $result;
                } else {
                    ddd($options);
                }
            }
        }
        return false;
    }

    private function tree_max_depth($tree=[]){
        $depth = 0;
        if(!is_array($tree)){
            return $depth;
        }
        foreach($tree as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('depth', $record)){
                if($record['depth'] > $depth){
                    $depth = $record['depth'];
                }
            }
        }
        return $depth;
    }

    private function tree_get_set(&$tree, $depth=0): array
    {
        $is_collect = false;
        $set = [];
        foreach($tree as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('depth', $record) &&
                $record['depth'] === $depth
            ){
                $is_collect = true;
            }
            if($is_collect){
                if(
                    is_array($record) &&
                    array_key_exists('depth', $record) &&
                    $record['depth'] <> $depth){
                    $is_collect = false;
                    break;
                }
                $set[] = $record;
            }
        }
        return $set;
    }

    private function tree_set_replace($tree=[], $set=[], $depth=0){
        $is_collect = false;
        foreach($tree as $nr => $record){
            if(
                $is_collect === false &&
                is_array($record) &&
                array_key_exists('depth', $record) &&
                $record['depth'] === $depth
            ){
                $is_collect = $nr;
                continue;
            }
            if($is_collect){
                if(
                    is_array($record) &&
                    array_key_exists('depth', $record) &&
                    $record['depth'] <> $depth){
                    $tree[$is_collect] = [];
                    $tree[$is_collect]['set'] = $set;
                    $is_collect = false;
                    break;
                }
                unset($tree[$nr]);
            }
        }
        return $tree;
    }

    /**
     * @throws Exception
     */
    private function where_convert($input=[]){
        if(is_array($input)){
            $is_string = true;
            foreach($input as $nr => $line){
                if(!is_string($line)){
                    $is_string = false;
                    break;
                }
            }
            if($is_string){
                $input = implode(' ', $input);
            }
        }
        $string = $input;
        if(!is_string($string)){
            return $string;
        }
        $tree = Token::tree('{' . $string . '}', [
            'with_whitespace' => true,
            'extra_operators' => [
                'and',
                'or',
                'xor'
            ]
        ]);
        $is_collect = false;
        $previous = null;
        $next = null;
        foreach($tree as $nr => $record){
            if(array_key_exists($nr - 1, $tree)){
                $previous = $nr - 1;
            }
            if(array_key_exists($nr - 2, $tree)){
                $next = $nr - 2;
            }
            if($record['type'] === Token::TYPE_CURLY_OPEN){
                unset($tree[$nr]);
            }
            elseif($record['type'] === Token::TYPE_CURLY_CLOSE){
                unset($tree[$nr]);
            }
            elseif($record['type'] === Token::TYPE_WHITESPACE){
                if(!empty($collection)){
                    if(array_key_exists($is_collect, $tree)){
                        $tree[$is_collect]['collection'] = $collection;
                        $tree[$is_collect]['type'] = Token::TYPE_COLLECTION;
                        $tree[$is_collect]['value'] = '';
                    }
                    $collection = [];
                }
                $is_collect = false;
                unset($tree[$nr]);
            }
            elseif($record['value'] === '('){
                $tree[$nr] = '(';
            }
            elseif($record['value'] === ')'){
                $tree[$nr] = ')';
            }
            elseif($is_collect === false && $record['value'] === '.'){
                $is_collect = true;
                $collection = [];
                $collection[] = $tree[$previous];
                unset($tree[$previous]);
            }
            elseif(
                in_array(
                strtolower($record['value']),
                [
                    'and',
                    'or',
                    'xor'
                ],
                true
                )
            ){
                $tree[$nr] = $record['value'];
            }
            if($is_collect === true){
                $collection[] = $record;
                $is_collect = $nr;
            }
            elseif($is_collect){
                $collection[] = $record;
                unset($tree[$nr]);
            }
        }
        if(!empty($collection)){
            if(array_key_exists($is_collect, $tree)){
                $tree[$is_collect]['collection'] = $collection;
                $tree[$is_collect]['type'] = Token::TYPE_COLLECTION;
                $tree[$is_collect]['value'] = '';
            }
            $collection = [];
        }
        $previous = null;
        $next = null;
        $list = [];
        foreach($tree as $nr => $record){
            $list[] = $record;
            unset($tree[$nr]);
        }
        foreach($list as $nr => $record){
            if(array_key_exists($nr - 1, $list)){
                $previous = $nr - 1;
            }
            if(array_key_exists($nr + 1, $list)){
                $next = $nr + 1;
            }
            if(!is_array($record)){
                continue;
            }
            if(
                array_key_exists('is_operator', $record) &&
                $record['is_operator'] === true
            ){
                $attribute = $this->tree_record_attribute($list[$previous]);
                $operator = $record['value'];
                $value = $this->tree_record_attribute($list[$next]);

                $list[$previous] = [
                    'attribute' => $attribute,
                    'value' => $value,
                    'operator' => $operator
                ];
                unset($list[$nr]);
                unset($list[$next]);
            }
            elseif(
                in_array(
                    strtolower($record['value']),
                    Filter::OPERATOR_LIST_NAME,
                    true
                )
            ){
                $attribute = $this->tree_record_attribute($list[$previous]);
                $operator = strtolower($record['value']);
                $value = $this->tree_record_attribute($list[$next]);
                $list[$previous] = [
                    'attribute' => $attribute,
                    'operator' => $operator,
                    'value' => $value
                ];
                unset($list[$nr]);
                unset($list[$next]);
            }
        }
        $tree = [];
        foreach($list as $nr => $record){
            $tree[] = $record;
            unset($list[$nr]);
        }
        return $tree;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    private function tree_record_attribute($record=[])
{
        $attribute = '';
        if(!array_key_exists('collection', $record)){
            switch($record['type']){
                case Token::TYPE_QUOTE_DOUBLE_STRING:
                    if(strpos($record['value'], '{') === false){
                        return substr($record['value'], 1, -1);
                    }
                    //parse string...
                    $object = $this->object();
                    $storage = $this->storage();
                    $parse = new Parse($object);
                    $result = $parse->compile($record['value'], $storage, $object);
                    if(
                        is_string($result) &&
                        substr($result, 0, 1) === '"' &&
                        substr($result, -1) === '"'
                    ){
                        $result = substr($result, 1, -1);
                    }
                    return $result;
                case Token::TYPE_QUOTE_SINGLE_STRING:
                    return substr($record['value'], 1, -1);
            }
            return array_key_exists('execute', $record) ? $record['execute'] : $record['value'];
        }
        if(!is_array($record['collection'])){
            switch($record['type']){
                case Token::TYPE_QUOTE_DOUBLE_STRING:
                case Token::TYPE_QUOTE_SINGLE_STRING:
                    return substr($record['value'], 1, -1);

            }
            return array_key_exists('execute', $record) ? $record['execute'] : $record['value'];
        }
        foreach($record['collection'] as $nr => $item){
            $attribute .= array_key_exists('execute', $item) ? $item['execute'] : $item['value'];
        }
        return $attribute;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function sync()
    {
        $object = $this->object();

        $url_object = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds')
        ;

        $dir = new Dir();
        $read = $dir->read($url_object);
        if(empty($read)){
            return;
        }
        foreach ($read as $file) {
            $class = File::basename($file->name, $object->config('extension.json'));
            $item = $object->data_read($file->url);
            $time_start = microtime(true);
            $dir_node = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds');
            $dir_binarysearch = $dir_node .
                'BinarySearch' .
                $object->config('ds')
            ;
            $dir_binarysearch_class = $dir_binarysearch .
                $class .
                $object->config('ds');

            $url = $dir_binarysearch_class .
                'Asc' .
                $object->config('ds') .
                'Uuid' .
                $object->config('extension.json');
            if(!File::exist($url)){
                continue;
            }
            $mtime = File::mtime($url);
            $meta_url = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds') .
                'Meta' .
                $object->config('ds') .
                $class .
                $object->config('extension.json');
            $data = $object->data_read($url);
            if (!$data) {
                continue;
            }
            $meta = $object->data_read($meta_url, sha1($meta_url));
            if (!$meta) {
                continue;
            }
            if(!$item){
                continue;
            }
            if ($item->has('sort')) {
                foreach ($item->get('sort') as $sort) {
                    $properties = explode(',', $sort);
                    foreach ($properties as $nr => $property) {
                        $properties[$nr] = trim($property);
                    }
                    $url_property_asc = false;
                    $url_property_asc_asc = false;
                    $url_property_asc_desc = false;
                    if(count($properties) > 1){
                        $dir_property_asc = $dir_binarysearch_class .
                            'Asc' .
                            $object->config('ds')
                        ;
                        $dir_property_asc_asc = $dir_property_asc .
                            'Asc' .
                            $object->config('ds')
                        ;
                        $dir_property_asc_desc = $dir_property_asc .
                            'Desc' .
                            $object->config('ds')
                        ;
                        $url_property_asc_asc = $dir_property_asc_asc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.json')
                        ;
                        $url_property_asc_desc = $dir_property_asc_desc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.json')
                        ;
                        $mtime_property = File::mtime($url_property_asc_asc);
                    } else {
                        $dir_property_asc = $dir_binarysearch_class .
                            'Asc' .
                            $object->config('ds')
                        ;
                        $url_property_asc = $dir_property_asc .
                            Controller::name(implode('-', $properties)) .
                            $object->config('extension.json')
                        ;
                        $url_property_desc = false;
                        $mtime_property = File::mtime($url_property_asc);
                    }
                    if ($mtime === $mtime_property) {
                        //same cache
                        continue;
                    }
                    if (empty($list)) {
                        $list = new Storage();
                        foreach ($data->data($class) as $uuid => $node) {
                            if (property_exists($node, 'uuid')) {
                                $storage_url = $object->config('project.dir.data') .
                                    'Node' .
                                    $object->config('ds') .
                                    'Storage' .
                                    $object->config('ds') .
                                    substr($node->uuid, 0, 2) .
                                    $object->config('ds') .
                                    $node->uuid .
                                    $object->config('extension.json');
                                $record = $object->data_read($storage_url);
                                if ($record) {
                                    //add filter for big fat objects
                                    $list->set($uuid, $record->data());
                                } else {
                                    //event out of sync, send mail
                                }
                            }
                        }
                    }
                    if (array_key_exists(1, $properties)) {
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC',
                            $properties[1] => 'ASC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $result = new Storage();
                        $index = 0;
                        foreach ($sort as $key1 => $subList) {
                            foreach ($subList as $key2 => $subSubList) {
                                $nodeList = [];
                                foreach ($subSubList as $nr => $node) {
                                    $item = $data->get($class . '.' . $node->uuid);
                                    $item->{'#index'} = $index;
                                    $item->{'#sort'} = new stdClass();
                                    $item->{'#sort'}->{$properties[0]} = $key1;
                                    $item->{'#sort'}->{$properties[1]} = $key2;
                                    $nodeList[] = $item;
                                    $index++;
                                }
                                if (empty($key1)) {
                                    $key1 = '""';
                                }
                                if (empty($key2)) {
                                    $key2 = '""';
                                }
                                $result->set($class . '.' . $key1 . '.' . $key2, $nodeList);
                            }
                        }
                        $lines = $result->write($url_property_asc_asc, 'lines');
                        File::touch($url_property_asc_asc, $mtime);
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC',
                            $properties[1] => 'DESC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $result = new Storage();
                        $index = 0;
                        foreach ($sort as $key1 => $subList) {
                            foreach ($subList as $key2 => $subSubList) {
                                $nodeList = [];
                                foreach ($subSubList as $nr => $node) {
                                    $item = $data->get($class . '.' . $node->uuid);
                                    $item->{'#index'} = $index;
                                    $item->{'#sort'} = new stdClass();
                                    $item->{'#sort'}->{$properties[0]} = $key1;
                                    $item->{'#sort'}->{$properties[1]} = $key2;
                                    $nodeList[] = $item;
                                    $index++;
                                }
                                if (empty($key1)) {
                                    $key1 = '""';
                                }
                                if (empty($key2)) {
                                    $key2 = '""';
                                }
                                $result->set($class . '.' . $key1 . '.' . $key2, $nodeList);
                            }
                        }
                        $lines_asc_desc = $result->write($url_property_asc_desc, 'lines');
                        File::touch($url_property_asc_desc, $mtime);
                    } else {
                        $sort = Sort::list($list)->with([
                            $properties[0] => 'ASC'
                        ], [
                            'output' => 'raw'
                        ]);
                        $result = new Storage();
                        $index = 0;
                        foreach ($sort as $key => $subList) {
                            $nodeList = [];
                            foreach ($subList as $nr => $node) {
                                $item = $data->get($class . '.' . $node->uuid);
                                $item->{'#index'} = $index;
                                $item->{'#sort'} = new stdClass();
                                $item->{'#sort'}->{$properties[0]} = $key;
                                $nodeList[] = $item;
                                $index++;
                            }
                            if (empty($key)) {
                                $key = '""';
                            }
                            $result->set($class . '.' . $key, $nodeList);
                        }
                        $lines = $result->write($url_property_asc, 'lines');
                        File::touch($url_property_asc, $mtime);
                    }
                    $count = $index;
                    $sortable = new Storage();
                    $sortable->set('property', $properties);
                    $sortable->set('count', $count);
                    $sortable->set('lines', $lines);
                    if(!empty($url_property_asc_asc)){
                        $sortable->set('url.asc.asc', $url_property_asc_asc);
                        $sortable->set('url.asc.desc', $url_property_asc_desc);
                    } else {
                        $sortable->set('url.asc', $url_property_asc);
                    }

                    $key = sha1(Core::object($properties, Core::OBJECT_JSON));
                    $meta->set('Sort.' . $class . '.' . $key, $sortable->data());
                    $meta->write($meta_url);
                    if ($object->config(Config::POSIX_ID) === 0) {
                        $command = 'chown www-data:www-data ' . $meta_url;
                        exec($command);
                    }
                    if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                        $command = 'chmod 666 ' . $meta_url;
                        exec($command);
                    }
                    if ($object->config(Config::POSIX_ID) === 0) {
                        if(!empty($url_property_asc_asc)){
                            $command = 'chown www-data:www-data ' . $dir_binarysearch;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_binarysearch_class;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_property_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_property_asc_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $dir_property_asc_desc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $url_property_asc_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $url_property_asc_desc;
                            exec($command);
                        } else {
                            $command = 'chown www-data:www-data ' . $dir_property_asc;
                            exec($command);
                            $command = 'chown www-data:www-data ' . $url_property_asc;
                            exec($command);
                        }
                    }
                    if ($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                        $command = 'chmod 777 ' . $dir_binarysearch;
                        exec($command);
                        $command = 'chmod 777 ' . $dir_binarysearch_class;
                        exec($command);
                        if(!empty($url_property_asc_asc)){
                            $command = 'chmod 777 ' . $dir_property_asc;
                            exec($command);
                            $command = 'chmod 777 ' . $dir_property_asc_asc;
                            exec($command);
                            $command = 'chmod 777 ' . $dir_property_asc_desc;
                            exec($command);
                            $command = 'chmod 666 ' . $url_property_asc_asc;
                            exec($command);
                            $command = 'chmod 666 ' . $url_property_asc_desc;
                            exec($command);
                        } else {
                            $command = 'chmod 777 ' . $dir_property_asc;
                            exec($command);
                            $command = 'chmod 666 ' . $url_property_asc;
                            exec($command);
                        }
                    }
                }
            }
            $time_end = microtime(true);
            $time_duration = round(($time_end - $time_start) * 1000, 2);
            echo 'Duration: ' . $time_duration . 'ms' . PHP_EOL;
        }
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    private function bin_search_list_create(App $object, $class, $options=[]): void
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
        $list = new Storage();
        $mtime = File::mtime($url);
        $response = [];
        foreach($data->data($class) as $uuid => $node) {
            if (property_exists($node, 'uuid')) {
                $storage_url = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Storage' .
                    $object->config('ds') .
                    substr($node->uuid, 0, 2) .
                    $object->config('ds') .
                    $node->uuid .
                    $object->config('extension.json')
                ;
                $record = $object->data_read($storage_url);
                if ($record) {
                    $list->set($uuid, $record->data());
                } else {
                    //event out of sync, send mail
                }
            }
        }
        foreach($meta->get('BinarySearch.' . $class)  as $property => $record){
            if($property === 'uuid'){
                continue;
            }
            $url_property = $dir_binarysearch .
                Controller::name($property) .
                $object->config('extension.json')
            ;
            $properties = explode('-', $property);
            if(array_key_exists(1, $properties)){
                $sort = Sort::list($list)->with([
                    $properties[0] => 'ASC',
                    $properties[1] => 'ASC'
                ], [
                    'output' => 'raw'
                ]);
                $result = new Storage();
                $index = 0;
                foreach($sort as $key1 => $subList){
                    foreach($subList as $key2 => $subSubList){
                        $nodeList = [];
                        foreach($subSubList as $nr => $node){
                            $item = $data->get($class . '.' . $node->uuid);
                            $item->{'#index'} = $index;
                            $item->{'#sort'} = new stdClass();
                            $item->{'#sort'}->{$properties[0]} = $key1;
                            $item->{'#sort'}->{$properties[1]} = $key2;
                            $nodeList[] = $item;
                            $index++;
                        }
                        if(empty($key1)){
                            $key1 = '""';
                        }
                        if(empty($key2)){
                            $key2 = '""';
                        }
                        $result->set($class . '.' . $key1 . '.' . $key2, $nodeList);
                    }
                }
            } else {
                $sort = Sort::list($list)->with([
                    $property => 'ASC'
                ], [
                    'output' => 'raw'
                ]);
                $result = new Storage();
                $index = 0;
                foreach($sort as $key => $subList){
                    $nodeList = [];
                    foreach($subList as $nr => $node){
                        $item = $data->get($class . '.' . $node->uuid);
                        $item->{'#index'} = $index;
                        $item->{'#sort'} = new stdClass();
                        $item->{'#sort'}->{$property} = $key;
                        $nodeList[] = $item;
                        $index++;
                    }
                    if(empty($key)){
                        $key = '""';
                    }
                    $result->set($class . '.' . $key, $nodeList);
                }
            }
            $record->lines = $result->write($url_property, 'lines');
            File::touch($url_property, $mtime);
            $record->count = $index;
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $url_property;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 666 ' . $url_property;
                exec($command);
            }
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
                    'lines'=> $record->lines,
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
                    'lines'=> $record->lines,
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
            //add where convert here
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
            d($url_property);
            d($limit);
            ddd($where_list);

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

    private function binary_search_node($file, $options=[]){
        $seek = $options['seek'];
        $seek--;
        $file->seek($seek);
        $depth = 0;
        $is_parent = false;
        $data = [];
        while($object = $file->current()) {
            $object_match = str_replace(' ', '', $object);
            $object_match = str_replace('"', '', $object_match);
            $object_explode = explode(':', $object_match);
            $symbol = trim($object_explode[0], " \t\n\r\0\x0B,");
            $symbol_right = null;
            if(array_key_exists(1, $object_explode)){
                $symbol_right = trim($object_explode[1], " \t\n\r\0\x0B,");
            }
            if(
                $symbol === '}' ||
                $symbol_right === '}'
            ){
                $depth--;
            }
            elseif(
                $symbol === '{' ||
                $symbol_right === '{'
            ){
                $depth++;
            }
            if($is_parent){
                if($depth === 0 && $symbol === '}'){
                    $data[] = $symbol;
                    $is_parent = false;
                    break;
                } else {
                    $data[] = ltrim($object, " \t");
                }
                $seek++;
            } else {
                if($depth === 1){
                    $depth = 0;
                    $is_parent = true;
                    continue;
                }
                $seek--;
                if($seek < 0){
                    break;
                }
            }
            $file->seek($seek);
        }
        if(!empty($data)){
            $record  = json_decode(implode('', $data));
            if(!is_object($record)){
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
        return false;
    }

    private function where_get_depth($where=[]){
        $depth = 0;
        $deepest = 0;
        if(!is_array($where)){
            return $depth;
        }
        foreach($where as $key => $value){
            if($value === '('){
                $depth++;
            }
            if($value === ')'){
                $depth--;
            }
            if($depth > $deepest){
                $deepest = $depth;
            }
        }
        return $deepest;
    }

    private function where_get_set(&$where=[], &$key=null, $deep=0){
        $set = [];
        $depth = 0;
        if(!is_array($where)){
            return $set;
        }
        foreach($where as $nr => $value){
            if($value === '('){
                $depth++;
            }
            if($value === ')'){
                if($depth === $deep){
                    unset($where[$nr]);
                    if(!empty($set)){
                        break;
                    }
                }
                $depth--;
                if(
                    $depth === $deep &&
                    !empty($set)
                ){
                    break;
                }
            }
            if($depth === $deep){
                if($key === null){
                    $key = $nr;
                }
                if(!in_array($value, [
                    '(',
                    ')'
                ], true)) {
                    $set[] = $value;
                }
                unset($where[$nr]);
            }
        }
        return $set;
    }

    /**
     * @throws Exception
     */
    private function where_process($record=[], $set=[], &$where=[], &$key=null, &$operator=null, $options=[]){
        if(
            array_key_exists(0, $set) &&
            count($set) === 1
        ){
            $operator = null;
            if($set[0] === true || $set[0] === false){
                $where[$key] = $set[0];
//                array_shift($set);
                return $set;
            }
            $list = [];
            $list[] = $record;
            $filter_where = [
                'node.' . $set[0]['attribute'] => [
                    'value' => $set[0]['value'],
                    'operator' => $set[0]['operator']
                ]
            ];
            $left = Filter::list($list)->where($filter_where);
            if(!empty($left)){
                $where[$key] = true;
                $set[0] = true;
            } else {
                $filter_where = [
                    $set[0]['attribute'] => [
                        'value' => $set[0]['value'],
                        'operator' => $set[0]['operator']
                    ]
                ];
                $left = Filter::list($list)->where($filter_where);
                if(!empty($left)){
                    $where[$key] = true;
                    $set[0] = true;
                } else {
                    $where[$key] = false;
                    $set[0] = false;
                }
            }
            return $set;
        }
        elseif(
            array_key_exists(0, $set) &&
            array_key_exists(1, $set) &&
            array_key_exists(2, $set)
        ){
            switch($set[1]){
                case 'or':
                    $operator = 'or';
                    if($set[0] === true || $set[2] === true){
                        $where[$key] = true;
                        return $set;
                    }
                    $list = [];
                    $list[] = $record;
                    if($set[0] === false){
                        $left = $set[0];
                    }
                    elseif(
                        is_array($set[0]) &&
                        array_key_exists('attribute', $set[0]) &&
                        array_key_exists('value', $set[0]) &&
                        array_key_exists('operator', $set[0])
                    ){
                        $filter_where = [
                            'node.' . $set[0]['attribute'] => [
                                'value' => $set[0]['value'],
                                'operator' => $set[0]['operator']
                            ]
                        ];
                        $left = Filter::list($list)->where($filter_where);
                    }
                    if($set[2] === false){
                        $right = $set[2];
                    }
                    elseif(
                        is_array($set[2]) &&
                        array_key_exists('attribute', $set[2]) &&
                        array_key_exists('value', $set[2]) &&
                        array_key_exists('operator', $set[2])
                    ){
                        $filter_where = [
                            'node.' . $set[2]['attribute'] => [
                                'value' => $set[2]['value'],
                                'operator' => $set[2]['operator']
                            ]
                        ];
                        $right = Filter::list($list)->where($filter_where);
                    }
                    if(!empty($left)){
                        $where[$key] = true;
                        $set[0] = true;
                    } else {
                        if(
                            is_array($set[0]) &&
                            array_key_exists('attribute', $set[0]) &&
                            array_key_exists('value', $set[0]) &&
                            array_key_exists('operator', $set[0])
                        ){
                            $filter_where = [
                                $set[0]['attribute'] => [
                                    'value' => $set[0]['value'],
                                    'operator' => $set[0]['operator']
                                ]
                            ];
                            $left = Filter::list($list)->where($filter_where);
                            if(!empty($left)){
                                $where[$key] = true;
                                $set[0] = true;
                            } else {
                                $set[0] = false;
                            }
                        }
                    }
                    if(!empty($right)){
                        $where[$key] = true;
                        $set[2] = true;
                    } else {
                        if(
                            is_array($set[2]) &&
                            array_key_exists('attribute', $set[2]) &&
                            array_key_exists('value', $set[2]) &&
                            array_key_exists('operator', $set[2])
                        ){
                            $filter_where = [
                                $set[2]['attribute'] => [
                                    'value' => $set[2]['value'],
                                    'operator' => $set[2]['operator']
                                ]
                            ];
                            $right = Filter::list($list)->where($filter_where);
                            if(!empty($right)){
                                $where[$key] = true;
                                $set[2] = true;
                            } else {
                                $set[2] = false;
                            }
                        }
                    }
                    if(!empty($left) || !empty($right)){
                        //nothing
                    } else {
                        $where[$key] = false;
                    }
                    return $set;
                case 'and':
                    $operator = 'and';
                    if($set[0] === false && $set[2] === false){
                        $where[$key] = false;
                        return $set;
                    }
                    $list = [];
                    $list[] = $record;
                    if(
                        is_array($set[0]) &&
                        is_array($set[2]) &&
                        array_key_exists('attribute', $set[0]) &&
                        array_key_exists('value', $set[0]) &&
                        array_key_exists('operator', $set[0]) &&
                        array_key_exists('attribute', $set[2]) &&
                        array_key_exists('value', $set[2]) &&
                        array_key_exists('operator', $set[2])
                    ){
                        $filter_where = [
                            'node.' . $set[0]['attribute'] => [
                                'value' => $set[0]['value'],
                                'operator' => $set[0]['operator']
                            ],
                            'node.' . $set[2]['attribute'] => [
                                'value' => $set[2]['value'],
                                'operator' => $set[2]['operator']
                            ]
                        ];
                        $and = Filter::list($list)->where($filter_where);
                        if(!empty($and)){
                            $where[$key] = true;
                            $set[0] = true;
                            $set[2] = true;
                        } else {
                            if(
                                is_array($set[0]) &&
                                is_array($set[2]) &&
                                array_key_exists('attribute', $set[0]) &&
                                array_key_exists('value', $set[0]) &&
                                array_key_exists('operator', $set[0]) &&
                                array_key_exists('attribute', $set[2]) &&
                                array_key_exists('value', $set[2]) &&
                                array_key_exists('operator', $set[2])
                            ){
                                $filter_where = [
                                    $set[0]['attribute'] => [
                                        'value' => $set[0]['value'],
                                        'operator' => $set[0]['operator']
                                    ],
                                    $set[2]['attribute'] => [
                                        'value' => $set[2]['value'],
                                        'operator' => $set[2]['operator']
                                    ]
                                ];
                                $and = Filter::list($list)->where($filter_where);
                                if(!empty($and)){
                                    $where[$key] = true;
                                    $set[0] = true;
                                    $set[2] = true;
                                } else {
                                    $where[$key] = false;
                                    $set[0] = false;
                                    $set[2] = false;
                                }
                            }
                        }
                        return $set;
                    }
                case 'xor' :
                    $operator = 'xor';
                    $list = [];
                    $list[] = $record;
                    if($set[1] === $operator){
                        $is_true = 0;
                        foreach($set as $nr => $true){
                            if(
                                is_array($true) &&
                                array_key_exists('attribute', $true) &&
                                array_key_exists('value', $true) &&
                                array_key_exists('operator', $true)
                            ){
                                $filter_where = [
                                    'node.' . $true['attribute'] => [
                                        'value' => $true['value'],
                                        'operator' => $true['operator']
                                    ]
                                ];
                                $current = Filter::list($list)->where($filter_where);
                                if(!empty($current)){
                                    $is_true++;
                                    $set[$nr] = true;
                                } else {
                                    $filter_where = [
                                        $true['attribute'] => [
                                            'value' => $true['value'],
                                            'operator' => $true['operator']
                                        ]
                                    ];
                                    $current = Filter::list($list)->where($filter_where);
                                    if(!empty($current)){
                                        $is_true++;
                                        $set[$nr] = true;
                                    } else {
                                        $set[$nr] = false;
                                    }
                                }
                            }
                            elseif($true === true){
                                $is_true++;
                            }
                        }
                        if($is_true === 1){
                            $where[$key] = true;
                            $set = [];
                            $set[0] = true;
                            return $set;
                        }
                        $where[$key] = false;
                        $set = [];
                        $set[0] = false;
                        return $set;
                    }
                    if($set[0] === false){
                        $left = $set[0];
                    }
                    elseif(
                        is_array($set[0]) &&
                        array_key_exists('attribute', $set[0]) &&
                        array_key_exists('value', $set[0]) &&
                        array_key_exists('operator', $set[0])
                    ){
                        $filter_where = [
                            'node.' . $set[0]['attribute'] => [
                                'value' => $set[0]['value'],
                                'operator' => $set[0]['operator']
                            ]
                        ];
                        $left = Filter::list($list)->where($filter_where);
                    }
                    if($set[2] === false){
                        $right = $set[2];
                    }
                    elseif(
                        is_array($set[2]) &&
                        array_key_exists('attribute', $set[2]) &&
                        array_key_exists('value', $set[2]) &&
                        array_key_exists('operator', $set[2])
                    ){
                        $filter_where = [
                            'node.' . $set[2]['attribute'] => [
                                'value' => $set[2]['value'],
                                'operator' => $set[2]['operator']
                            ]
                        ];
                        $right = Filter::list($list)->where($filter_where);
                    }
                    if(!empty($left)){
                        $set[0] = true;
                    }
                    elseif(
                        is_array($set[0]) &&
                        array_key_exists('attribute', $set[0]) &&
                        array_key_exists('value', $set[0]) &&
                        array_key_exists('operator', $set[0])
                    ){
                        $filter_where = [
                            $set[0]['attribute'] => [
                                'value' => $set[0]['value'],
                                'operator' => $set[0]['operator']
                            ]
                        ];
                        $left = Filter::list($list)->where($filter_where);
                        if(!empty($left)){
                            $where[$key] = true;
                            $set[0] = true;
                        } else {
                            $set[0] = false;
                        }
                    }
                    if(!empty($right)){
                        $set[2] = true;
                    }
                    elseif(
                        is_array($set[2]) &&
                        array_key_exists('attribute', $set[2]) &&
                        array_key_exists('value', $set[2]) &&
                        array_key_exists('operator', $set[2])
                    ){
                        $filter_where = [
                            $set[2]['attribute'] => [
                                'value' => $set[2]['value'],
                                'operator' => $set[2]['operator']
                            ]
                        ];
                        $right = Filter::list($list)->where($filter_where);
                        if(!empty($right)){
                            $where[$key] = true;
                            $set[2] = true;
                        } else {
                            $set[2] = false;
                        }
                    }
                    if(!empty($left) || !empty($right)){
                        if(!empty($left) && !empty($right)){
                            $where[$key] = false;
                            array_shift($set);
                            array_shift($set);
                            $set[0] = false;
                        } else {
                            $where[$key] = true;
                            array_shift($set);
                            array_shift($set);
                            $set[0] = true;
                        }
                    } else {
                        $where[$key] = false;
                        array_shift($set);
                        array_shift($set);
                        $set[0] = false;
                    }
                    return $set;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function where($record=[], $where=[], $options=[]){
        if(empty($where)){
            return $record;
        }
        if(
            is_string($where) ||
            is_array($where)
        ){
            $where = $this->where_convert($where);
        }
        $deepest = $this->where_get_depth($where);
        $counter =0;
        while($deepest >= 0){
            if($counter > 1024){
                break;
            }
            $set = $this->where_get_set($where, $key, $deepest);
            while($record !== false){
                $set = $this->where_process($record, $set, $where, $key, $operator, $options);
                if(empty($set) && $deepest === 0){
                    return $record;
                }
                $count_set = count($set);
                if($count_set === 1){
                    if($operator === null && $set[0] === true){
                        break;
                    } else {
                        if($deepest === 0){
                            $record = false;
                            break 2;
                        } else {
                            break;
                        }
                    }
                }
                elseif($count_set >= 3){
                    switch($operator){
                        case 'and':
                            if($set[0] === false && $set[2] === false){
                                array_shift($set);
                                array_shift($set);
                                $set[0] = false;
                            }
                            elseif($set[0] === true && $set[2] === true){
                                array_shift($set);
                                array_shift($set);
                                $set[0] = true;
                            }
                            break;
                        case 'or':
                            if($set[0] === true || $set[2] === true){
                                array_shift($set);
                                array_shift($set);
                                $set[0] = true;
                            } else {
                                array_shift($set);
                                array_shift($set);
                                $set[0] = false;
                            }
                            break;
                    }
                }
                $counter++;
                if($counter > 1024){
                    break 2;
                }
            }
            if($record === false){
                break;
            }
            if($deepest === 0){
                break;
            }
            ksort($where, SORT_NATURAL);
            $deepest = $this->where_get_depth($where);
            unset($key);
            $counter++;
        }
        return $record;
    }

    /**
     * @throws Exception
     */
    private function filter($record=[], $filter=[], $options=[]){

        $list = [];
        $list[] = $record;
        $list = Filter::list($list)->where($filter);
        if(!empty($list)){
            return $record;
        }
        return false;
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
            ]);
            d($options['url']);
            d($record);
            d($i);
            if($record){
                $read = $object->data_read($record->{'#read'}->url, sha1($record->{'#read'}->url));
                if($read){
                    $record = Core::object_merge($record, $read->data());
                }
                d($options['where']);
                d($record);
                //add expose
                if(!empty($options['filter'])){
                    $record = $this->filter($record, $options['filter'], $options);
                }
                elseif(!empty($options['where'])){
                    $record = $this->where($record, $options['where'], $options);
                }
                if($record){
                    ddd($record);
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

    private function binary_search_index($file, $options=[]){
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
        $direction = 'down';
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
                if(array_key_exists(1, $explode)){
                    if($explode[0] === '#index') {
                        $direction = 'down';
                        $index = (int) trim($explode[1], " \t\n\r\0\x0B,");
                        if ($options['index'] === $index) {
                            return $this->binary_search_node($file, [
                                'seek' => $seek,
                                'lines' => $options['lines'],
                                'index' => $index,
                                'counter' => $options['counter']
                            ]);
                        }
                        elseif(
                            $options['index'] < $index
                        ){
                            $options['max'] = $seek - 1;
                            break;
                        }
                        elseif(
                            $options['index'] > $index
                        ){
                            $options['min'] = $seek + 1;
                            break;
                        }
                    }
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


    private function bin_search_index($file, $options=[]){
        if(!array_key_exists('counter', $options)){
            $options['counter'] = 0;
        }
        if(!array_key_exists('search', $options)){
            $options['search'] = [];
        }
        if(!array_key_exists('direction', $options)){
            $options['direction'] = 'down';
        }
        if(!array_key_exists('lines', $options)){
            return false;
        }
        if(!array_key_exists('seek', $options)){
            $options['seek'] = (int) (0.5 * $options['lines'] - 1);
        }
        if(
            $options['direction'] === 'down' &&
            !in_array($options['seek'], $options['search'], true)
        ){
            $options['search'][] = $options['seek'];
        }
        elseif($options['direction'] === 'down') {
            //not found
            return false;
        }
        if(!array_key_exists('index', $options)){
            return false;
        }
        $file->seek($options['seek']);
        $seek = $options['seek'];

        while($line = $file->current()){
            $options['counter']++;
            if($options['counter'] > 1024){
                //log error with filesize of view
                break;
            }
            $line_match = str_replace(' ', '', $line);
            $line_match = str_replace('"', '', $line_match);
            $explode = explode(':', $line_match);
            $index = false;
            if(array_key_exists(1, $explode)){
                if($explode[0] === '#index') {
                    $direction = 'down';
                    $index = (int)trim($explode[1], " \t\n\r\0\x0B,");
                    if ($options['index'] === $index) {
                        return $this->binary_search_node($file, [
                            'seek' => $seek,
                            'lines' => $options['lines'],
                            'index' => $index,
                            'counter' => $options['counter']
                        ]);
                    }
                    elseif(
                        $options['index'] > $index
                    ){
                        $options['seek'] = (int) (1.5 * $options['seek']);
                        if($options['seek'] > $options['lines']){
                            $options['seek'] = $options['lines'] - 1;
                            $options['direction'] = 'up';
                        }
                        return $this->bin_search_index($file, $options);
                    }
                    elseif(
                        $options['index'] < $index
                    ){
                        $options['seek'] = (int) (0.5 * $options['seek']);
                        return $this->bin_search_index($file, $options);
                    }
                }
            }
            if($options['direction'] === 'up'){
                $seek--;
                if($seek < 0){
                    $options['direction'] = 'down';
                    $seek = 0;
                }
                $file->seek($seek);
            } else {
                $seek++;
                $file->next();
                if($seek === $options['lines'] - 1){
                    $options['direction'] = 'up';
                }
            }
        }
        return false;
    }

    /* old no usage?
    private function binary_search($file, $options=[]){
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
    */

    private function dir(App $object, $dir=[]){
        if(
            array_key_exists('uuid', $dir) &&
            array_key_exists('node', $dir) &&
            array_key_exists('data', $dir)
        ){
            if(!Dir::is($dir['uuid'])) {
                Dir::create($dir['uuid'], Dir::CHMOD);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['uuid'];
                exec($command);
                $command = 'chmod 777 ' . $dir['node'];
                exec($command);
                $command = 'chmod 777 ' . $dir['data'];
                exec($command);
            }
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $dir['uuid'];
                exec($command);
                $command = 'chown www-data:www-data ' . $dir['node'];
                exec($command);
                $command = 'chown www-data:www-data ' . $dir['data'];
                exec($command);
            }
        }
        if(array_key_exists('meta', $dir)){
            if(!Dir::is($dir['meta'])) {
                Dir::create($dir['meta'], Dir::CHMOD);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 777 ' . $dir['meta'];
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['meta'];
                    exec($command);
                }
            }
        }
        if(array_key_exists('validate', $dir)){
            if(!Dir::is($dir['validate'])) {
                Dir::create($dir['validate'], Dir::CHMOD);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 777 ' . $dir['validate'];
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['validate'];
                    exec($command);
                }
            }
        }
        if(array_key_exists('binary_search_class', $dir)){
            if(!Dir::is($dir['binary_search_class'])) {
                Dir::create($dir['binary_search_class'], Dir::CHMOD);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 777 ' . $dir['binary_search_class'];
                    exec($command);
                    $command = 'chmod 777 ' . Dir::name($dir['binary_search_class']);
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['binary_search_class'];
                    exec($command);
                    $command = 'chown www-data:www-data ' . Dir::name($dir['binary_search_class']);
                    exec($command);
                }
            }
        }
        if(array_key_exists('binary_search', $dir)){
            if(!Dir::is($dir['binary_search'])) {
                Dir::create($dir['binary_search'], Dir::CHMOD);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 777 ' . $dir['binary_search'];
                    exec($command);
                }
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['binary_search'];
                    exec($command);
                }
            }
        }
    }
}