<?php

namespace R3m\Io\Node\Trait;

use Exception;
use R3m\Io\App;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\File;
use R3m\Io\Node\Service\User;

Trait Expose {

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
        d($expose);
        ddd($roles);
        foreach($roles as $role) {
            if(
                property_exists($role, 'permission') &&
                is_array($role->permission)
            ){
                foreach ($role->permission as $permission) {
                    if (is_array($permission)) {
                        ddd($permission);
                    }
                    foreach ($expose as $action) {
                        if (
                            property_exists($permission, 'name') &&
                            $permission->name === $class . '.' . $function &&
                            $action->role === $role->name()
                        ) {
                            ddd('found');
                        }
                    }
                }
            }
                    /*
            foreach ($role->permission as $permission) {
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

                }
            }
*/

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
    public function expose_get(App $object, $name='', $attribute=''){
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
}