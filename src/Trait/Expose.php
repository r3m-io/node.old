<?php

namespace R3m\Io\Node\Trait;

use Exception;
use R3m\Io\App;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\File;
use R3m\Io\Node\Service\User;

Trait Expose {

    /**
     * @throws ObjectException
     * @throws Exception
     * @throws AuthorizationException
     */
    public function expose($node, $expose=[], $class='', $function='', $internalRole=false, $parentRole=false): Storage
    {
        $object = $this->object();
        if (!is_array($expose)) {
            return new Storage();
        }
        $roles = [];
        if ($internalRole) {
            $roles[] = $internalRole; //same as parent
        } else {
//            $roles = Permission::getAccessControl($object, $class, $function);
            try {
                $user = User::getByAuthorization($object);
                if ($user) {
                    $roles = $user->getRolesByRank('asc');
                }
            } catch (Exception $exception) {

            }
        }
        if (empty($roles)) {
            throw new Exception('Roles failed...');
        }
        $record = [];
        d($roles);
        foreach ($roles as $role) {
            if (
                property_exists($role, 'uuid') &&
                property_exists($role, 'name') &&
                $role->name === 'ROLE_SYSTEM' &&
                !property_exists($role, 'permission')
            ) {
                $permission = [];
                $permission['uuid'] = Core::uuid();
                $permission['name'] = $class . '.' . $function;
                $permission['attribute'] = [];
                $permission['role'] = $role->uuid;
                $role->permission = [];
                $role->permission[] = (object) $permission;
            }
            if (
                property_exists($role, 'name') &&
                property_exists($role, 'permission') &&
                is_array($role->permission)
            ) {
                foreach ($role->permission as $permission) {
                    if (is_array($permission)) {
                        ddd($permission);
                    }
                    foreach ($expose as $action) {
                        if (
                            (
                                property_exists($permission, 'name') &&
                                $permission->name === $class . '.' . $function &&
                                property_exists($action, 'role') &&
                                $action->role === $role->name
                            )
                            ||
                            (
                                in_array(
                                    $function,
                                    ['child', 'children'],
                                    true
                                ) &&
                                property_exists($action, 'role') &&
                                $action->role === $parentRole
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
                                        $record_attribute = $node->get($explode[0]);
                                        $compare = $explode[1];
                                        $attribute = $explode[0];
                                        if ($compare) {
                                            $parse = new Parse($object, $object->data());
                                            $compare = $parse->compile($compare, $object->data());
                                            d($node);
                                            ddd($compare);
                                            if ($record_attribute !== $compare) {
                                                throw new Exception('Assertion failed: ' . $assertion . ' values [' . $record_attribute . ', ' . $compare . ']');
                                            }
                                        }
                                    }
                                    if (
                                        property_exists($action, 'objects') &&
                                        property_exists($action->objects, $attribute) &&
                                        property_exists($action->objects->$attribute, 'expose')
                                    ) {
                                        if (
                                            property_exists($action->objects->$attribute, 'multiple') &&
                                            $action->objects->$attribute->multiple === true &&
                                            $node->has($attribute)
                                        ) {
                                            $record[$attribute] = [];
                                            $array = $node->get($attribute);
                                            if(is_array($array) || is_object($array)){
                                                foreach ($array as $child) {
                                                    $child = new Storage($child);
                                                    $child_expose =[];
                                                    if(
                                                        property_exists($action->objects->$attribute, 'objects')
                                                    ){
                                                        $child_expose[] = (object) [
                                                            'attributes' => $action->objects->$attribute->expose,
                                                            'objects' => $action->objects->$attribute->objects,
                                                            'role' => $action->role,
                                                        ];
                                                    }  else {
                                                        $child_expose[] = (object) [
                                                            'attributes' => $action->objects->$attribute->expose,
                                                            'role' => $action->role,
                                                        ];
                                                    }
                                                    $child = $this->expose(
                                                        $child,
                                                        $child_expose,
                                                        $attribute,
                                                        'child',
                                                        $role,
                                                        $action->role
                                                    );
                                                    $record[$attribute][] = $child->data();
                                                }
                                            }
                                        } elseif (
                                            $node->has($attribute)
                                        ) {
                                            $record[$attribute] = null;
                                            $child = $node->get($attribute);
                                            if (!empty($child)) {
                                                $child = new Storage($child);
                                                $child_expose =[];
                                                if(
                                                    property_exists($action->objects->$attribute, 'objects')
                                                ){
                                                    $child_expose[] = (object) [
                                                        'attributes' => $action->objects->$attribute->expose,
                                                        'objects' => $action->objects->$attribute->objects,
                                                        'role' => $action->role,
                                                    ];
                                                }  else {
                                                    $child_expose[] = (object) [
                                                        'attributes' => $action->objects->$attribute->expose,
                                                        'role' => $action->role,
                                                    ];
                                                }
                                                $child = $this->expose(
                                                    $child,
                                                    $child_expose,
                                                    $attribute,
                                                    'child',
                                                    $role,
                                                    $action->role
                                                );
                                                $record[$attribute] = $child->data();
                                            }
                                            if (empty($record[$attribute])) {
                                                $record[$attribute] = null;
                                            }
                                        }
                                    } else {
                                        if ($node->has($attribute)) {
                                            $record[$attribute] = $node->get($attribute);
                                        }
                                    }
                                }
                            }
                        } else {
                            d($class);
                            d($function);
                            d($action);
                            d($permission);
                        }
                        break 3;
                    }
                }
            }
        }
        return new Storage((object) $record);
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