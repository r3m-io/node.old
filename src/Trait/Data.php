<?php

namespace R3m\Io\Node\Trait;

//use R3m\Io\Module\Filter;
use R3m\Io\Module\Parse;
use R3m\Io\Module\Parse\Token;
use R3m\Io\Node\Service\Permission;
use R3m\Io\Node\Service\User;
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
    use BinarySearch;
    use Tree;
    use Where;
    use Filter;

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

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function read($class='', $options=[]): false|array|object
    {
//        $name = Controller::name($class);
        $options = Core::object($options, Core::OBJECT_ARRAY);
        $object = $this->object();
        d($class);
        d($options);

        $one = [];
        $one['sort']['name'] = 'asc';
        if(is_array($options)){
            foreach($options as $key => $value){
                $one['filter'][$key] = $value;
            }
            $data = $this->one($class, $one);
            return $data->data();
        } else {
            return false;
        }
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
     * @throws Exception
     */
    public function one($class='', $options=[]): false|Storage
    {
        $name = Controller::name($class);
        $options = Core::object($options, Core::OBJECT_ARRAY);
        $function = __FUNCTION__;
        $object = $this->object();
        $dir = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinarySearch' .
            $object->config('ds') .
            $name .
            $object->config('ds')
        ;
        if(array_key_exists('sort', $options)) {
            $properties = [];
            $has_descending = false;
            foreach ($options['sort'] as $key => $order) {
                if (empty($properties)) {
                    $properties[] = $key;
                    $order = 'asc';
                } else {
                    $properties[] = $key;
                    $order = strtolower($order);
                    if ($order === 'desc') {
                        $has_descending = true;
                    }
                }
                $dir .= ucfirst($order) . $object->config('ds');
            }
            $property = implode('-', $properties);
            $url = $dir .
                Controller::name($property) .
                $object->config('extension.json');
            if (!File::exist($url)) {
                return false;
            }

            $mtime = File::mtime($url);
            $sort_key = sha1(Core::object($properties, Core::OBJECT_JSON));
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
            $lines = $meta->get('Sort.' . $class . '.' . $sort_key . '.lines');
            $list = [];
            if (
                File::exist($url) &&
                $lines > 0
            ) {
                $file = new SplFileObject($url);
                $list = $this->binary_search_page($file, [
                    'filter' => $options['filter'],
                    'limit' => 1,
                    'page' => 1,
                    'lines' => $lines,
                    'counter' => 0,
                    'direction' => 'next',
                    'url' => $url
                ]);
            }
            if(array_key_exists(0, $list)){
                return new Storage($list[0]);
            }
        }
        return false;
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

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function sync()
    {
        $object = $this->object();
        $options = App::options($object);
        if(property_exists($options, 'class')){
            $options->class = explode(',', $options->class);
            foreach($options->class as $nr => $class){
                $options->class[$nr] = Controller::name(trim($class));
            }
        }
        $url_object = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds')
        ;
        $exception = [
            'Role'
        ];
        $dir = new Dir();
        $read = $dir->read($url_object);
        if(empty($read)){
            return;
        }
        foreach ($read as $file) {
            $expose = false;
            $class = File::basename($file->name, $object->config('extension.json'));
            if(property_exists($options, 'class')){
                if(!in_array($class, $options->class, 1)){
                    continue;
                }
            }
            if(in_array($class, $exception, 1)){

            } else {
                $role = $this->read('Role', [
                    'name' => 'ROLE_SYSTEM'
                ]);
                $expose = $this->expose_get(
                    $object,
                    $class,
                    $class . '.' . __FUNCTION__ . '.expose'
                );
                ddd($role);
            }
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
                                    if(in_array($class, $exception, true)){
                                        $list->set($uuid, $record->data());
                                    }
                                    elseif($expose) {
                                        $record = $this->expose(
                                            $object,
                                            $record,
                                            $expose,
                                            $class,
                                            __FUNCTION__,
                                            $role
                                        );
                                        ddd($record);
                                    }
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
     * @throws AuthorizationException
     */
    public static function expose(App $object, $record, $expose=[], $class='', $function='', $internalRole=false, $parentScope=false): array
    {
        if(!is_array($expose)){
            return false;
        }
        d($expose);
        ddd($record);

        $roles = [];
        if($internalRole){
            $roles[] = $internalRole; //same as parent
        } else {
//            $roles = Permission::getAccessControl($object, $class, $function);
            try {
                $user = User::getByAuthorization($object);
                if($user){
                    $roles = $user->getRolesByRank('asc');
                }
            } catch (Exception $exception){

            }
        }
        if(empty($roles)){
            throw new Exception('Roles failed...');
        }

        /*
        if(
            method_exists($node, 'setObject') &&
            method_exists($node, 'getObject')
        ){
            $test = $node->getObject();
            if(empty($test)){
                $node->setObject($object);
            }
        }

        if(is_array($entity)){
            ddd($entity);
        }
        if(is_array($function)){
            $debug = debug_backtrace(true);
            ddd($debug[0]);
            ddd($function);

        }
        foreach($roles as $role){
            $permissions = $role->getPermissions();
            foreach ($permissions as $permission){
                if(is_array($permission)){
                    ddd($permission);
                }
                foreach($toArray as $action) {
                    if(
                        (
                            $permission->getName() === $entity . '.' . $function &&
                            property_exists($action, 'scope') &&
                            $action->scope === $permission->getScope()
                        ) ||
                        (
                            in_array(
                                $function,
                                ['child', 'children']
                            ) &&
                            property_exists($action, 'scope') &&
                            $action->scope === $parentScope
                        )
                    ) {
                        if (
                            property_exists($action, 'attributes') &&
                            is_array($action->attributes)
                        ) {
                            foreach ($action->attributes as $attribute) {
                                $assertion = $attribute;
                                $explode = explode(':', $attribute, 2);
                                $compare = null;
                                if (array_key_exists(1, $explode)) {
                                    $methods = explode('_', $explode[0]);
                                    foreach ($methods as $nr => $method) {
                                        $methods[$nr] = ucfirst($method);
                                    }
                                    $method = 'get' . implode($methods);
                                    $compare = $explode[1];
                                    $attribute = $explode[0];
                                    if ($compare) {
                                        $parse = new Parse($object, $object->data());
                                        $compare = $parse->compile($compare, $object->data());
                                        if ($node->$method() !== $compare) {
                                            throw new Exception('Assertion failed: ' . $assertion . ' values [' . $node->$method() . ', ' . $compare . ']');
                                        }
                                    }
                                } else {
                                    $methods = explode('_', $attribute);
                                    foreach ($methods as $nr => $method) {
                                        $methods[$nr] = ucfirst($method);
                                    }
                                    $method = 'get' . implode($methods);
                                }
                                if (
                                    property_exists($action, 'objects') &&
                                    property_exists($action->objects, $attribute) &&
                                    property_exists($action->objects->$attribute, 'toArray')
                                ) {
                                    if (
                                        property_exists($action->objects->$attribute, 'multiple') &&
                                        $action->objects->$attribute->multiple === true &&
                                        method_exists($node, $method)
                                    ) {
                                        $record[$attribute] = [];
                                        $array = $node->$method();
                                        foreach ($array as $child) {
                                            $child_entity = explode('Entity\\', get_class($child));
                                            $child_record = [];
                                            $child_record = $this->expose(
                                                $object,
                                                $child,
                                                $action->objects->$attribute->toArray,
                                                $child_entity[1],
                                                'children',
                                                $child_record,
                                                $role,
                                                $action->scope
                                            );
                                            $record[$attribute][] = $child_record;
                                        }
                                    } elseif (
                                        method_exists($node, $method)
                                    ) {
                                        $record[$attribute] = [];
                                        $child = $node->$method();
                                        if (!empty($child)) {
                                            $child_entity = explode('Entity\\', get_class($child));
                                            $record[$attribute] = $this->expose(
                                                $object,
                                                $child,
                                                $action->objects->$attribute->toArray,
                                                $child_entity[1],
                                                'child',
                                                $record[$attribute],
                                                $role,
                                                $action->scope
                                            );
                                        }
                                        if (empty($record[$attribute])) {
                                            $record[$attribute] = null;
                                        }
                                    }
                                } else {
                                    if (method_exists($node, $method)) {
                                        $record[$attribute] = $node->$method();
                                    }
                                }
                            }
                        }
                        break 3;
                    }
                }
            }
        }
        */
        return $record;
    }
    /**
     * @throws ObjectException
     * @throws Exception
     */
    protected function expose_get(App $object, $name='', $attribute=''){
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_expose = $dir_node .
            'Expose' .
            $object->config('ds')
        ;
        $url = $dir_expose .
            $name .
            $object->config('extension.json')
        ;
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

    private function dir(App $object, $dir=[]): void
    {
        if(
            array_key_exists('uuid', $dir)
        ){
            if(!Dir::is($dir['uuid'])) {
                Dir::create($dir['uuid'], Dir::CHMOD);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['uuid'];
                exec($command);
            }
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $dir['uuid'];
                exec($command);
            }
        }
        if(
            array_key_exists('node', $dir)
        ){
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['node'];
                exec($command);
            }
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $dir['node'];
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