<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\Module\Cli;
use R3m\Io\Node\Service\Security;
use SplFileObject;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Validate;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Data {
    use BinaryTree;
    use Tree;
    use Where;
    use Filter;
    use Expose;
    use Role;

    use Data\Count;
    use Data\Create;
    use Data\Delete;
    use Data\Export;
    use Data\Import;
    use Data\NodeList;
    use Data\Patch;
    use Data\Put;
    use Data\Read;
    use Data\Record;
    use Data\Rename;
    use Data\Sync;
    use Data\Truncate;

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function dictionary(){
        $object = $this->object();
        $source = $object->config('project.dir.data') . 'App' . $object->config('ds') . 'Dictionary.English' . $object->config('extension.json');
        $destination = $object->config('project.dir.data') . 'App' . $object->config('ds') . 'Dictionary' . $object->config('ds') . 'English' . $object->config('extension.json');

        $data = $object->data_read($source);
        $dictionary = new Storage();
        $index = 0;
        $list = [];
        if($data){
            foreach($data->data() as $word => $unused){
                $record = [];
                $record['word'] = $word;
                $record['#key'] = $index;
                $record['#class'] = 'App.Dictionary.English';
                $record['uuid'] = Core::uuid();
                $list[]= $record;
                $index++;
            }
            $dictionary->set('App.Dictionary.English', $list);
            $dictionary->write($destination);
            echo 'App.Dictionary.English created in: ' . $destination . PHP_EOL;
        }
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
     * @throws Exception
     */
    public function one($class='', $options=[]): false|Storage
    {
        ddd('deprecated');
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
                $list = $this->binary_search_page(
                    $object,
                    $file,
                    $counter,
                    [
                        'filter' => $options['filter'],
                        'limit' => 1,
                        'page' => 1,
                        'lines' => $lines,
                        'counter' => 0,
                        'direction' => 'next',
                        'url' => $url,
                        'ramdisk' => true,
                        'mtime' => $mtime,
                    ]
                );
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
    protected function validate(App $object, $url, $type){
        $data = $object->data(sha1($url));
        if($data === null){
            $data = $object->parse_read($url, sha1($url));
        }
        if($data){
            $clone = $data->data($type . '.validate');
            if(is_object($clone)){
                $validate = clone $clone;
                if(empty($validate)){
                    throw new Exception('No validation found for ' . $type . ' in ' . $url . '.');
                }
                return Validate::validate($object, $validate);
            } else {
                throw new Exception('No validation found for ' . $type . ' in ' . $url . '.');
            }
        }
        return false;
    }

    private function dir(App $object, $dir=[]): void
    {


        if(
            array_key_exists('uuid', $dir)
        ){
            if(!Dir::is($dir['uuid'])) {
                Dir::create($dir['uuid'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['uuid'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['uuid'];
                exec($command);
            }
        }
        if(
            array_key_exists('node', $dir)
        ){
            if(!Dir::is($dir['node'])) {
                Dir::create($dir['node'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['node'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['node'];
                exec($command);
            }
        }
        if(array_key_exists('meta', $dir)){
            if(!Dir::is($dir['meta'])) {
                Dir::create($dir['meta'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['meta'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['meta'];
                exec($command);
            }
        }
        if(array_key_exists('validate', $dir)){
            if(!Dir::is($dir['validate'])) {
                Dir::create($dir['validate'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['validate'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['validate'];
                exec($command);
            }
        }
        if(array_key_exists('binary_tree_class', $dir)){
            if(!Dir::is($dir['binary_tree_class'])) {
                Dir::create($dir['binary_tree_class'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['binary_tree_class'];
                    exec($command);
                    $command = 'chown www-data:www-data ' . Dir::name($dir['binary_tree_class']);
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['binary_tree_class'];
                exec($command);
                $command = 'chmod 777 ' . Dir::name($dir['binary_tree_class']);
                exec($command);
            }
        }
        if(array_key_exists('binary_tree_asc', $dir)){
            if(!Dir::is($dir['binary_tree_asc'])) {
                Dir::create($dir['binary_tree_asc'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['binary_tree_asc'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['binary_tree_asc'];
                exec($command);
            }
        }
        if(array_key_exists('binary_tree', $dir)){
            if(!Dir::is($dir['binary_tree'])) {
                Dir::create($dir['binary_tree'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['binary_tree'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['binary_tree'];
                exec($command);
            }
        }
        if(
            array_key_exists('ramdisk', $dir) &&
            !is_bool($dir['ramdisk'])
        ){
            if(!Dir::is($dir['ramdisk'])) {
                Dir::create($dir['ramdisk'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['ramdisk'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['ramdisk'];
                exec($command);
            }
        }
        if(array_key_exists('commit', $dir)){
            if(!Dir::is($dir['commit'])) {
                Dir::create($dir['commit'], Dir::CHMOD);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir['commit'];
                    exec($command);
                }
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir['commit'];
                exec($command);
            }
        }
    }

    public function module($url=''){
        $object = $this->object();
        $explode = explode('/', str_replace($object->config('controller.dir.view'), '', $url));
        foreach($explode as $nr => $record){
            $explode[$nr] = File::basename($record, $object->config('extension.tpl'));
        }
        if(array_key_exists(0, $explode)){
            return $explode[0];
        }
    }

    public function submodule($url=''){
        $object = $this->object();
        $explode = explode('/', str_replace($object->config('controller.dir.view'), '', $url));
        foreach($explode as $nr => $record){
            $explode[$nr] = File::basename($record, $object->config('extension.tpl'));
        }
        if(array_key_exists(1, $explode)){
            return $explode[1];
        }
    }

    public function command($url=''){
        $object = $this->object();
        $explode = explode('/', str_replace($object->config('controller.dir.view'), '', $url));
        foreach($explode as $nr => $record){
            $explode[$nr] = File::basename($record, $object->config('extension.tpl'));
        }
        if(array_key_exists(2, $explode)){
            return $explode[2];
        }
    }

    public function subcommand($url=''){
        $object = $this->object();
        $explode = explode('/', str_replace($object->config('controller.dir.view'), '', $url));
        foreach($explode as $nr => $record){
            $explode[$nr] = File::basename($record, $object->config('extension.tpl'));
        }
        if(array_key_exists(3, $explode)){
            return $explode[3];
        }
    }

    /**
     * @throws Exception
     */
    public function object_create($class, $role, $node=[], $options=[])
    {
        $name = Controller::name($class);
        $object = $this->object();
        $object->request('node', (object) $node);
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
        $dir_object = $dir_node .
            'Object'.
            $object->config('ds')
        ;
        $dir_binary_tree = $dir_node .
            'BinaryTree'.
            $object->config('ds')
        ;
        $dir_binary_tree_class = $dir_binary_tree .
            $name .
            $object->config('ds')
        ;
        if(!array_key_exists('function', $options)){
            $options['function'] = str_replace('_', '.', __FUNCTION__);
        }
        $options['relation'] = false;
        $force = false;
        if(array_key_exists('force', $options)){
            $force = $options['force'];
            unset($options['force']);
        }
        if(!Security::is_granted(
            'Data',
            $role,
            $options
        )){
            return false;
        }
        $url = $dir_object . $name . $object->config('extension.json');
        $dir_binary_tree_asc = $dir_binary_tree_class .
            'Asc' .
            $object->config('ds')
        ;
        $url_binary_tree = $dir_binary_tree_asc .
            'Uuid' .
            $object->config('extension.btree')
        ;
        if(
            $force === true ||
            (
                !File::exist($url) &&
                !File::exist($url_binary_tree)
            )
        ){
            $item = [];
            $item['Node'] = [];
            $item['Node']['#class'] = $name;
            $item['Node']['type'] = 'object';
            $item['Node']['property'] = $this->object_create_property($object, $name);
            $item['sort'] = $this->object_create_sort($object, $name);
            $item['is.unique'] = $this->object_create_is_unique($object, $name);
            $item['sync'] = $this->object_create_sync($object, $name);
            $item = (object) $item;
            Dir::create($dir_object, Dir::CHMOD);
            Dir::create($dir_binary_tree_asc, Dir::CHMOD);
            File::write($url, Core::object($item, Core::OBJECT_JSON));
            File::touch($url_binary_tree);
            $expose = $this->object_create_expose($object, $name, $item);
            $this->sync_file([
                'dir_object' => $dir_object,
                'dir_binary_tree_asc' => $dir_binary_tree_asc,
                'url' => $url,
                'url_binary_tree' => $url_binary_tree,
            ]);
        } else {
            throw new Exception('Object already exists: ' . $url . ' or ' . $url_binary_tree . '.');
        }
    }

    public function object_create_expose(App $object, $class, $item): Storage
    {
        $data = new Storage();
        $item = new Storage($item);
        $expose = new Storage();
        $expose->set('role', 'ROLE_SYSTEM');

        $attributes = [];
        $properties = $item->get('Node.property');
        d($properties);
        if($properties){
            foreach($properties as $nr => $property){
                if(property_exists($property, 'name')){
                    $attributes[] = $property->name;
                }
            }
        }
        $expose->set('attributes', $attributes);

        $objects = $this->object_create_expose_object($object, $class, $item->get('Node.property'));

        $expose->set('objects', $objects);


        ddd($expose->data());

        return $data;
    }

    public function object_create_expose_object($object, $class, $properties=[]){
        $result = [];
        foreach($properties as $nr => $property){
            if(
                property_exists($property, 'name') &&
                property_exists($property, 'type') &&
                $property->type === 'object'
            ){
                if(property_exists($property, 'property')){
                    $expose = [];
                    $objects = [];
                    foreach($property->property as $object_property){
                        if(property_exists($object_property, 'name')){
                            $expose[] = $object_property->name;
                        }
                        if(
                            property_exists($object_property, 'type') &&
                            $object_property->type === 'object'
                        ){
                            $objects[$object_property->name] = $this->object_create_expose_object($object, $class, $object_property->property);
                        }
                    }
                }
                $multiple = false;
                if(property_exists($property, 'multiple')){
                    $multiple = $property->multiple;
                }
                if(!empty($objects)){
                    $result['objects'][$property->name] = [
                        'multiple' => $multiple,
                        'expose' => $expose,
                        'objects' => $objects
                    ];
                } else {
                    $result['objects'][$property->name] = [
                        'multiple' => $multiple,
                        'expose' => $expose
                    ];
                }
            }
        }
        return $result;
    }

    public function object_create_sync(App $object, $class): object
    {
        $sync = [];
        $sync['interval'] = (int) Cli::read('input', 'What is the "sync" interval: ');
        if($sync['interval'] < 60){
            $sync['interval'] = 60;
        }
        return (object) $sync;
    }

    /**
     * @throws ObjectException
     */
    public function object_create_sort(App $object, $class): array
    {
        $sort = [];
        echo 'Leave "sort" empty if finished.' . PHP_EOL;
        while(true){
            echo 'Enter the property of the "sort"' . PHP_EOL;
            $name = Cli::read('input', '(use a , to use multiple properties): ');
            if(empty($name)){
                break;
            }
            $sort[] = $name;
        }
        return $sort;
    }

    /**
     * @throws ObjectException
     */
    public function object_create_is_unique(App $object, $class): array
    {
        $is_unique = [];
        echo 'Leave "is unique" empty if finished.' . PHP_EOL;
        while(true){
            echo 'Enter the property of the "is unique"' . PHP_EOL;
            $name = Cli::read('input', '(use a , to use multiple properties): ');
            if(empty($name)){
                break;
            }
            $is_unique[] = $name;
        }
        return $is_unique;
    }

    /**
     * @throws ObjectException
     */
    public function object_create_property(App $object, $class){
        $properties = [];
        echo 'Leave "name" empty if finished.' . PHP_EOL;
        while(true){
            $name = Cli::read('input', 'Enter the "name" of the property: ');
            if(empty($name)){
                break;
            }
            echo 'Available types:' . PHP_EOL;
            echo '    - array' . PHP_EOL;
            echo '    - boolean' . PHP_EOL;
            echo '    - float' . PHP_EOL;
            echo '    - int' . PHP_EOL;
            echo '    - null' . PHP_EOL;
            echo '    - object' . PHP_EOL;
            echo '    - string' . PHP_EOL;
            echo '    - uuid' . PHP_EOL;
            $type = Cli::read('input', 'Enter the "type" of the property: ');
            while(
                !in_array(
                    $type,
                    [
                        'array',
                        'boolean',
                        'float',
                        'int',
                        'null',
                        'object',
                        'string',
                        'uuid',
                    ],
                    true
                )
            ){
                echo 'Available types:' . PHP_EOL;
                echo '    - array' . PHP_EOL;
                echo '    - boolean' . PHP_EOL;
                echo '    - float' . PHP_EOL;
                echo '    - int' . PHP_EOL;
                echo '    - null' . PHP_EOL;
                echo '    - object' . PHP_EOL;
                echo '    - string' . PHP_EOL;
                echo '    - uuid' . PHP_EOL;
                $type = Cli::read('input', 'Enter the "type" of the property: ');
            }
            if($type === 'object'){
                $is_multiple = Cli::read('input', 'Are there multiple objects (y/n): ');
                if($is_multiple === 'y'){
                    $is_multiple = true;
                } else {
                    $is_multiple = false;
                }
                echo 'Please enter the "properties" of the object.' . PHP_EOL;
                $has_property_properties = [];
                while(true){
                    $has_property_name = Cli::read('input', 'Enter the "name" of the property: ');
                    if(empty($has_property_name)){
                        break;
                    }
                    echo 'Available types:' . PHP_EOL;
                    echo '    - array' . PHP_EOL;
                    echo '    - boolean' . PHP_EOL;
                    echo '    - float' . PHP_EOL;
                    echo '    - int' . PHP_EOL;
                    echo '    - null' . PHP_EOL;
                    echo '    - object' . PHP_EOL;
                    echo '    - string' . PHP_EOL;
                    echo '    - uuid' . PHP_EOL;
                    $has_property_type = Cli::read('input', 'Enter the "type" of the property: ');
                    if($has_property_type === 'object'){
                        $has_property_is_multiple = Cli::read('input', 'Are there multiple objects (y/n): ');
                        if($has_property_is_multiple === 'y'){
                            $has_property_is_multiple = true;
                        } else {
                            $has_property_is_multiple = false;
                        }
                        $has_property_properties[] = [
                            'name' => $has_property_name,
                            'type' => $has_property_type,
                            'property' => $this->object_create_property($object, $class),
                            'multiple' => $has_property_is_multiple
                        ];
                    } else {
                        $has_property_properties[] = [
                            'name' => $has_property_name,
                            'type' => $has_property_type
                        ];
                    }
                }
                $properties[] = [
                    'name' => $name,
                    'type' => $type,
                    'property' => $has_property_properties,
                    'multiple' => $is_multiple
                ];
                echo 'Object added...' . PHP_EOL;
            } else {
                $properties[] = [
                    'name' => $name,
                    'type' => $type
                ];
            }
        }
        return $properties;
    }
}