<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\Module\Filter;
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
                    $record->index = $count;
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
                $meta->set('BinarySearch.' . $class . '.uuid.lines', $lines);
                $meta->set('BinarySearch.' . $class . '.uuid.count', $count);
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
            $property = [];
            $has_descending = false;
            foreach($options['sort'] as $key => $order){
                $property[] = $key;
                if(strtolower($order) === 'desc'){
                    $has_descending = true;
                }
            }
            $property = implode('-', $property);
            $url = $dir .
                Controller::name($property) .
                $object->config('extension.json')
            ;
            $mtime = File::mtime($url);
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
                $options['where2'] = $this->where_convert($options);
                ddd($options);
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
                        'filter' => $options['filter'],
                        'page' => $options['page'],
                        'limit' => $options['limit'],
                        'lines'=> $lines,
                        'counter' => 0,
                        'direction' => 'next',
                        'url' => $where_url,
                        'debug' => true
                    ]);
                } else {
                    $lines = $meta->get('BinarySearch.' . $class . '.' . $property . '.lines');
                    $file = new SplFileObject($url);
                    $list = $this->binary_search_page($file, [
                        'where' => $options['where'],
                        'filter' => $options['filter'],
                        'page' => $options['page'],
                        'limit' => $options['limit'],
                        'lines'=> $lines,
                        'counter' => 0,
                        'direction' => 'next',
                        'url' => $url
                    ]);
                }
                $result = [];
                $result['page'] = $options['page'];
                $result['limit'] = $options['limit'];
                $result['list'] = $list;
                $result['sort'] = $options['sort'];
                $result['where'] = $options['where'] ?? [];
                //add filter
                return $result;

            }
            ddd($url);
        }
        return false;
    }

    private function tree_max_depth($tree=[]){
        $depth = 0;
        if(!is_array($tree)){
            return $depth;
        }
        foreach($tree as $nr => $record){
            if(array_key_exists('depth', $record)){
                if($record['depth'] > $depth){
                    $depth = $record['depth'];
                }
            }
        }
        return $depth;
    }

    private function tree_get_set(&$tree, $depth=0){
        $is_collect = false;
        $set = [];
        foreach($tree as $nr => $record){
            if($record['depth'] === $depth){
                $is_collect = true;
            }
            if($is_collect){
                if($record['depth'] <> $depth){
                    $is_collect = false;
                    break;
                }
                $set[] = $record;
                unset($tree[$nr]);
            }
        }
        return $set;
    }

    /**
     * @throws Exception
     */
    private function where_convert($options){
        if(!array_key_exists('wherestring', $options)){
            return $options['where'] ?? [];
        }
        $tree = Token::tree('{' . $options['wherestring'] . '}', [
            'with_whitespace' => true,
            'extra_operators' => [
                'and',
                'or',
                'xor'
            ]
        ]);
        while(!empty($tree)){
            $max_depth = $this->tree_max_depth($tree);
            $set = $this->tree_get_set($tree, $max_depth);
            $is_collect = false;
            $collection = [];
            foreach($set as $nr => $record){
                if($is_collect !== false){
                    if(
                        in_array(
                            $record['type'],
                            [
                                Token::TYPE_WHITESPACE
                            ],
                            true
                        )
                    ){
                        if(!empty($collection)){
                            $set[$is_collect]['collection'] = $collection;
                            $set[$is_collect]['type'] = Token::TYPE_COLLECTION;
                            $set[$is_collect]['value'] = '';
                        }
                        $is_collect = false;
                        unset($set[$nr]);
                    } else {
                        $collection[] = $record;
                        unset($set[$nr]);
                    }
                }
                if(array_key_exists($nr + 1, $set)){
                    $next = $set[$nr + 1];
                }
                if(
                    $record['type'] === Token::TYPE_STRING &&
                    $next['type'] === Token::TYPE_DOT &&
                    empty($collection)
                ){
                    $is_collect = $nr;
                    $collection[] = $record;
                }
                if(
                    in_array(
                        strtolower($record['value']),
                        [
                            'and',
                            'or',
                            'xor'
                        ],
                        true)
                ){
                    $set[$nr] = $record['value'];
                }
                elseif(
                    in_array(
                        $record['type'],
                        [
                            Token::TYPE_PARENTHESE_OPEN,
                            Token::TYPE_PARENTHESE_CLOSE,
                        ],
                        true
                    )
                ){
                    $set[$nr] = $record['value'];
                }
                elseif(
                    in_array(
                        $record['type'],
                        [
                        Token::TYPE_WHITESPACE
                        ],
                        true
                    )
                ){
                    unset($set[$nr]);
                }
            }
            ksort($set, SORT_NATURAL);
            $list = [];
            foreach($set as $nr => $record){
                $list[] = $record;
            }
            foreach($list as $nr => $record){
                $previous = false;
                $next = false;
                if(array_key_exists($nr - 1, $list)){
                    $previous = $list[$nr - 1];
                    unset($list[$nr - 1]);
                }
                if(array_key_exists($nr + 1, $list)){
                    $next = $list[$nr + 1];
                    unset($list[$nr + 1]);
                }
                if(
                    is_array($record) &&
                    array_key_exists('is_operator', $record) &&
                    $record['is_operator'] === true
                ){
                    $left = $previous;
                    if(is_array($left)){
                        if(array_key_exists('collection', $left)){
                            $attribute = $this->tree_collection_attribute($left);
                        }
                        elseif(array_key_exists('type', $left) && $left['type'] === Token::TYPE_STRING){
                            $attribute = $left['value'];
                            //parse value
                        }
                        elseif(
                            array_key_exists('type', $left) &&
                            in_array(
                                $left['type'],
                                [
                                    Token::TYPE_QUOTE_DOUBLE_STRING,
                                    Token::TYPE_QUOTE_SINGLE_STRING
                                ],
                                true
                            )
                        ){
                            $attribute = substr($left['value'], 1, -1);
                            //parse when double quote
                        }
                        else {
                            $attribute = $left['execute'] ?? $left['value'];
                        }
                    }
                    $right = $next;
                    if(is_array($right)){
                        if(array_key_exists('collection', $right)){
                            $value = $this->tree_collection_attribute($right);
                        }
                        elseif(array_key_exists('type', $right) && $right['type'] === Token::TYPE_STRING){
                            $value = $right['value'];
                        }
                        elseif(
                            array_key_exists('type', $right) &&
                            in_array(
                                $right['type'],
                                [
                                    Token::TYPE_QUOTE_DOUBLE_STRING,
                                    Token::TYPE_QUOTE_SINGLE_STRING
                                ],
                                true
                            )
                        ){
                            $value = substr($right['value'], 1, -1);
                            //parse when double quote
                        } else {
                            $value = $right['execute'] ?? $right['value'];
                        }
                    }
                    $list[$nr] = [
                        'attribute' => $attribute,
                        'operator' => $record['value'],
                        'value' => $value
                    ];
                }
            }
//            $left = $this->tree_set_get_left($set);
            ddd($list);

        }
    }

    private function tree_collection_attribute($record=[]): string
{
        $attribute = '';
        if(!array_key_exists('collection', $record)){
            return $attribute;
        }
        if(!is_array($record['collection'])){
            return $attribute;
        }
        foreach($record['collection'] as $nr => $record){
            $attribute .= $record['value'];
        }
        return $attribute;
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
                            $item->index = $index;
                            $item->sort = new stdClass();
                            $item->sort->{$properties[0]} = $key1;
                            $item->sort->{$properties[1]} = $key2;
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
                        $item->index = $index;
                        $item->sort = new stdClass();
                        $item->sort->{$property} = $key;
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
            $record->count = $index;
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $url_property;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 666 ' . $url_property;
                exec($command);
            }
            if(array_key_exists('where', $options)){
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
                            'index' => $index,
                            'key' => $key
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
                    $count = $index + 1;
                    $meta->set('Where.' . $class . '.' . $key . '.lines', $lines);
                    $meta->set('Where.' . $class . '.' . $key . '.count', $count);
                    $meta->set('Where.' . $class . '.' . $key . '.limit', $limit);
                    $meta->set('Where.' . $class . '.' . $key . '.mtime', $mtime);
                    $meta->set('Where.' . $class . '.' . $key . '.atime', null);;
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
            $record->read = new stdClass();
            $record->read->load = $options['counter'];
            $record->read->seek = $options['seek'];
            $record->read->lines = $options['lines'];
            $record->read->percentage = round(($options['counter'] / $options['lines']) * 100, 2);
            $object = $this->object();
            $record->read->url = $object->config('project.dir.data') .
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

    private function filter_where_get_depth($where=[]){
        $depth = 0;
        $deepest = 0;
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

    private function filter_where_get_set(&$where=[], &$key=null, $deep=0){
        $set = [];
        $depth = 0;
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
    private function filter_where_process($record=[], $set=[], &$where=[], &$key=null, &$operator=null){
        if(
            array_key_exists(0, $set) &&
            count($set) === 1
        ){
            if($set[0] === true || $set[0] === false){
                $where[$key] = $set[0];
                array_shift($set);
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
            } else {
                $where[$key] = false;
            }
            array_shift($set);
            $operator = null;
            return $set;
        }
        elseif(
            array_key_exists(0, $set) &&
            array_key_exists(1, $set) &&
            array_key_exists(2, $set)
        ){
            switch($set[1]){
                case 'or':
                    if($set[0] === true || $set[2] === true){
                        $where[$key] = true;
                        array_shift($set);
                        array_shift($set);
                        $set[0] = true;
                        return $set;
                    }
                    $list = [];
                    $list[] = $record;
                    if($set[0] === false){
                        $left = $set[0];
                    } else {
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
                    } else {
                        $filter_where = [
                            'node.' . $set[2]['attribute'] => [
                                'value' => $set[2]['value'],
                                'operator' => $set[2]['operator']
                            ]
                        ];
                        $right = Filter::list($list)->where($filter_where);
                    }
                    if(!empty($left) || !empty($right)){
                        $where[$key] = true;
                    } else {
                        $where[$key] = false;
                    }
                    array_shift($set);
                    array_shift($set);
                    $set[0] = $where[$key];
                    $operator =  'or';
                    return $set;
                case 'and':
                    if($set[0] === false && $set[2] === false){
                        $where[$key] = false;
                        array_shift($set);
                        array_shift($set);
                        $set[0] = false;
                        return $set;
                    }
                    $list = [];
                    $list[] = $record;
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
                    } else {
                        $where[$key] = false;
                    }
                    array_shift($set);
                    array_shift($set);
                    $set[0] = $where[$key];
                    $operator =  'and';
                    return $set;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function filter_where($record=[], $where=[], $options=[]){
        $deepest = $this->filter_where_get_depth($where);
        $counter =0;
        while($deepest >= 0){
            if($counter > 1024){
                break;
            }
            $set = $this->filter_where_get_set($where, $key, $deepest);
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

            /*
            while($set = $this->filter_where_process($record, $set, $where, $key, $operator)){
                $counter++;
                $count_set = count($set);
                d($count_set);
                d($operator);
                if(
                    $count_set === 1 &&
                    $set[0] === true &&
                    $operator === null
                ){
                    break;
                }
                elseif(
                    $count_set === 1 &&
                    $set[0] === false &&
                    $operator === null
                ){
                    $record = false;
                    break;
                }
                if(empty($set)){
                    break;
                }
            }
            */
//            d($deepest);
//            d($where);
//            d($record);
            if($record === false){
                break;
            }
            if($deepest === 0){
                break;
            }
            ksort($where, SORT_NATURAL);
            $deepest = $this->filter_where_get_depth($where);
            unset($key);
            $counter++;
        }
        return $record;
    }

    /**
     * @throws Exception
     */
    private function filter($record=[], $options=[]){
        /*
         * make an array of true and false and if all are boolean then process, so we can implement xor xor
         */
        $record = $this->filter_where($record, $options['where'] ?? [], $options);
        return $record;

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
                $read = $object->data_read($record->read->url, sha1($record->read->url));
                if($read){
                    $record->node = $read->data();
                }
                if(array_key_exists('debug', $options)){
                    unset($options['filter']);
                }
                $record = $this->filter($record, $options);
                if($record){
                    $page[] = $record;
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
        $time_start = microtime(true);
        for($i = $start; $i < $end; $i++){
            $record = $this->binary_search_index($file, [
                'lines'=> $options['lines'],
                'counter' => 0,
                'index' => $i,
                'search' => [],
            ]);
            if($record){
                $read = $object->data_read($record->read->url, sha1($record->read->url));
                if($read){
                    $record->node = $read->data();
                }
                if(array_key_exists('where', $options) && !empty($options['where'])){
                    $record = $this->filter($record, $options);
                }
                if($record){
                    $page[] = $record;
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
                    if($explode[0] === 'index') {
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
                if($explode[0] === 'index') {
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
}