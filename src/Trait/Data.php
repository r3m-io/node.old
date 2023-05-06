<?php

namespace R3m\Io\Node\Trait;

//use R3m\Io\Module\Filter;

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
use R3m\Io\Module\Parse;

use R3m\Io\Node\Service\Role;
use R3m\Io\Node\Service\User;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Data {
    use BinarySearch;
    use Tree;
    use Where;
    use Filter;

    use Data\Create;
    use Data\Delete;
    use Data\NodeList;
    use Data\Patch;
    use Data\Put;
    use Data\Read;
    use Data\Record;
    use Data\Sync;

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
                    [
                        'filter' => $options['filter'],
                        'limit' => 1,
                        'page' => 1,
                        'lines' => $lines,
                        'counter' => 0,
                        'direction' => 'next',
                        'url' => $url
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
    public static function expose(App $object, $record, $expose=[], $class='', $function='', $internalRole=false, $parentScope=false): Storage
    {
        if(!is_array($expose)){
            return false;
        }
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
        d($class);
        d($function);
        foreach($roles as $role) {
            $permissions = $role->permissions();
            foreach ($permissions as $permission) {
                if (is_array($permission)) {
                    ddd($permission);
                }
                foreach ($expose as $action) {
                    if(
                        property_exists($permission, 'name') &&
                        $permission->name === $class . '.' . $function &&
                        $action->role === $role->name()
                    ){
                        ddd('found');
                    }
                    /*
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
                    */
                }
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
        }
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
}