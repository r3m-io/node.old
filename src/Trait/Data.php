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
        if(!Security::is_granted(
            'Data',
            $role,
            $options
        )){
            return false;
        }
        $properties = $this->object_create_property($object, $class);
        echo 'here we are...';
        die;


        /*
        $data = $object->data_read($object->config('project.dir.data') . 'Node' . $object->config('ds') . 'BinaryTree' . $object->config('ds') . $class . $object->config('extension.json'));
        if($data){
            $data = $data->data();
            $data['#class'] = $class;
            $data['#key'] = $object->config('node.key');
            $data['uuid'] = Core::uuid();
            $data['#node'] = $object->config('node.key');
            $data['#node'] = $object->config('node.key');
            $data['#node'] = $object->config('node
        */
    }

    /**
     * @throws ObjectException
     */
    public function object_create_property(App $object, $class){
        $properties = [];
        while(true){
            $name = Cli::read('input', 'Enter the name of the property: ');
            if(empty($name)){
                break;
            }
            echo 'Available types:' . PHP_EOL;
            echo '    - string' . PHP_EOL;
            echo '    - int' . PHP_EOL;
            echo '    - float' . PHP_EOL;
            echo '    - boolean' . PHP_EOL;
            echo '    - array' . PHP_EOL;
            echo '    - object' . PHP_EOL;
            echo '    - null' . PHP_EOL;
            echo '    - uuid' . PHP_EOL;
            $type = Cli::read('input', 'Enter the type of the property: ');
            while(
                !in_array(
                    $type,
                    [
                        'string',
                        'int',
                        'float',
                        'boolean',
                        'array',
                        'object',
                        'null',
                        'uuid',
                    ],
                    true
                )
            ){
                echo 'Available types:' . PHP_EOL;
                echo '    - string' . PHP_EOL;
                echo '    - int' . PHP_EOL;
                echo '    - float' . PHP_EOL;
                echo '    - boolean' . PHP_EOL;
                echo '    - array' . PHP_EOL;
                echo '    - object' . PHP_EOL;
                echo '    - null' . PHP_EOL;
                echo '    - uuid' . PHP_EOL;
                $type = Cli::read('input', 'Enter the type of the property: ');
            }
            $has_propery = Cli::read('input', 'Does this property has properties ? (y/n): ');
            if($has_propery === 'y'){
                $has_property_properties = [];
                while(true){
                    $has_property_name = Cli::read('input', 'Enter the name of the property: ');
                    if(empty($has_property_name)){
                        break;
                    }
                    echo 'Available types:' . PHP_EOL;
                    echo '    - string' . PHP_EOL;
                    echo '    - int' . PHP_EOL;
                    echo '    - float' . PHP_EOL;
                    echo '    - boolean' . PHP_EOL;
                    echo '    - array' . PHP_EOL;
                    echo '    - object' . PHP_EOL;
                    echo '    - null' . PHP_EOL;
                    echo '    - uuid' . PHP_EOL;
                    $has_property_type = Cli::read('input', 'Enter the type of the property: ');
                    $has_property_has_property = Cli::read('input', 'Does this property has properties ? (y/n): ');
                    if($has_property_has_property === 'y'){
                        $has_property_properties[] = [
                            'name' => $has_property_name,
                            'type' => $has_property_type,
                            'property' => $this->object_create_property($object, $class)
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
                    'property' => $has_property_properties
                ];
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