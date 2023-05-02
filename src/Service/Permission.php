<?php
namespace R3m\Io\Node\Service;

use Entity\Role;
use Entity\User as Entity;

use Exception;

use R3m\Io\App;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data;
use R3m\Io\Module\Database;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Handler;
use R3m\Io\Module\Parse;

use R3m\Io\Exception\ErrorException;
use R3m\Io\Exception\AuthorizationException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Exception\FileWriteException;

use Doctrine\ORM\Exception\ORMException;
use stdClass;

class Permission extends Main
{

    const SCOPE_SYSTEM = 'system';
    const SCOPE_USER = 'user';
    const SCOPE_PRIVATE = 'private';
    const SCOPE_PUBLIC = 'public';

    const CACHE_TIME = 20;  //minutes


    public static function has(Entity $user, $name): bool
    {
        $user_permissions = [];
        foreach($user->getRoles() as $role){
            $permissions = $role->getPermissions();
            if(
                $permissions &&
                is_array($permissions)
            ){
                foreach($permissions as $permission){
                    $user_permissions[] = $permission->getName();
                }
            }
        }
        if(in_array($name, $user_permissions)){
            return true;
        }
        return false;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public static function get(App $object, $entity='', $attribute=''){
        $dir = $object->config('project.dir.source') . 'Permission' . $object->config('ds');
        $url = $dir . $entity . $object->config('extension.json');
        if(!File::exist($url)){
            $explode = explode('.', $entity);
            if(array_key_exists(1, $explode)){
                $attribute = explode($entity, $attribute, 2);
                $explode = array_reverse($explode);
                $entity = implode('.', $explode);
                $attribute[0] = $entity;
                $attribute = implode('', $attribute);
                $url = $dir . $entity . $object->config('extension.json');
                if(!File::exist($url)){
                    throw new Exception('Permission url (' . $url . ') not found for entity: ' . $entity);
                }
            } else {
                throw new Exception('Permission url (' . $url . ') not found for entity: ' . $entity);
            }
        }
        $data = $object->data_read($url);
        if($data){
            $get = $data->get($attribute);
            if(empty($get)){
                throw new Exception('Cannot find attribute (' . $attribute .') in entity: ' . $entity);
            }
            return $get;
        }
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws \Doctrine\DBAL\Exception
     * @throws ORMException
     * @throws \Doctrine\ORM\ORMException
     */
    public static function getAccessControl(App $object, $entity=null, $action=''): array
    {
        $access_control = $object->config('access_control');
        if(!is_array($access_control)){
            $parse = new Parse($object, $object->data());
            $access_control = $parse->compile($access_control, $object->data());
            $object->config('access_control', $access_control);
        }
        $roles = [];
        $entity = 'Role';
        $entityManager = Database::entityManager($object, ['name'=> Main::API]);
        $repository = $entityManager->getRepository($object->config('doctrine.entity.prefix') . $entity);
        if(is_array($access_control)){
            foreach($access_control as $access){
                if(
                    property_exists($access, 'entity') &&
                    property_exists($access, 'action') &&
                    property_exists($access, 'roles') &&
                    $access->entity === $entity &&
                    $access->action === $action
                ){
                    foreach($access->roles as $name){
                        $role = $repository->findOneBy([
                            'name' => $name
                        ]);
                        if($role){
                            $roles[] = $role;
                        }
                    }
                    break;
                }
            }
        }
        return $roles;
    }

    /**
     * @throws ObjectException
     * @throws ErrorException
     * @throws \Doctrine\DBAL\Exception
     * @throws ORMException
     * @throws \Doctrine\ORM\ORMException
     * @throws FileWriteException
     * @throws Exception
     */
    public static function controller(App $object, $controller=null, $action='', &$user=null): ?Role
    {
        $url = false;
        try {
            $user = User::getByKey($object);
            if(!$user){
                $user = User::getByAuthorization($object);
            }
            if(
                !empty($user) &&
                is_object($user) &&
                $user->getUuid()
            ){
                $session = $object->session('user');
                if($session){
                    //read roles from session.
                    $has_role = false;
                    $roles = $object->session('user.roles');
                    foreach($roles as $role){
                        if(array_key_exists('permissions', $role)){
                            foreach($role['permissions'] as $permission){
                                if(
                                    $has_role === false &&
                                    array_key_exists('name', $permission) &&
                                    $permission['name'] === $controller . '.' . $action
                                ){
                                    $has_permission = true;
                                    if(
                                        array_key_exists('name', $role) &&
                                        array_key_exists('rank', $role)
                                    ){
                                        $has_role = new Role();
                                        $has_role->setName($role['name']);
                                        $has_role->setRank($role['rank']);
                                        break 2;
                                    }
                                }
                            }
                        }
                    }
                    if($has_permission && $has_role){
                        return $has_role;
                    }
                }  else {
                    $has_permission = false;
                    $has_role = false;
                    $roles = $user->getRolesByRank('asc');
                    $user_roles = [];
                    foreach($roles as $role) {
                        $user_role = [
                            'id' => $role->getId(),
                            'name' => $role->getName(),
                            'rank' => $role->getRank(),
                            'permissions' => []
                        ];
                        $permissions = $role->getPermissions();
                        $has_permission = false;
                        foreach ($permissions as $permission) {
                            $user_role['permissions'][] = [
                                'id' => $permission->getId(),
                                'name' => $permission->getName(),
                                'attributes' => $permission->getAttributes(),
                                'scope' => $permission->getScope()
                            ];
                            if (
                                $has_role === false &&
                                $permission->getName() === $controller . '.' . $action
                            ) {
                                $has_permission = true;
                                $has_role = $role;
                            }
                        }
                        $user_roles[] = $user_role;
                    }
                    $session = $user->session($object);
                    $session['roles'] = $user_roles;
                    $object->session('user', $session);
                    if($has_permission && $has_role){
                        return $has_role;
                    }
                }
            }
        } catch (Exception $exception){
            if(!$user){
                $entity = 'Role';
                $entityManager = Database::entityManager($object, ['name'=> Main::API]);
                $repository = $entityManager->getRepository($object->config('doctrine.entity.prefix') . $entity);
                $role = $repository->findOneBy([
                    'name' => 'ROLE_ANONYMOUS'
                ]);
                $permissions = $role->getPermissions();
                $has_permission = false;
                foreach($permissions as $permission){
                    if($permission->getName() === $controller . '.' . $action){
                        $has_permission = true;
                        break;
                    }
                }
                if($has_permission){
                    return $role;
                }
//                throw new ErrorException('Need permission ('. $controller .'.' . $action .')...');
                throw new AuthorizationException('You don\'t have permission to access this resource. (' . $controller . '.' . $action . ')');
            }
        }
        if($user){
            $session = $object->session('user');
            if($session){
                $roles = $object->session('user.roles');
                $has_permission = false;
                $has_role = false;
                foreach($roles as $role){
                    if(array_key_exists('permissions', $role)){
                        $permissions = $role['permissions'];
                        foreach($permissions as $permission){
                            if(
                                $has_role === false &&
                                array_key_exists('name', $permission) &&
                                $permission['name'] === $controller . '.' . $action
                            ){
                                $has_permission = true;
                                if(
                                    array_key_exists('name', $role) &&
                                    array_key_exists('rank', $role)
                                ){
                                    $has_role = new Role();
                                    $has_role->setName($role['name']);
                                    $has_role->setRank($role['rank']);
                                    break 2;
                                }
                            }
                        }
                    }
                }
            } else {
                $user_roles = [];
                $roles = $user->getRolesByRank('asc');
                $has_permission = false;
                $has_role = false;
                $role = false;
                foreach($roles as $role){
                    $user_role = [
                        'id' => $role->getId(),
                        'name' => $role->getName(),
                        'rank' => $role->getRank(),
                        'permissions' => []
                    ];
                    $permissions = $role->getPermissions();
                    foreach($permissions as $permission){
                        $user_role['permissions'][] = [
                            'id' => $permission->getId(),
                            'name' => $permission->getName(),
                            'attributes' => $permission->getAttributes(),
                            'scope' => $permission->getScope()
                        ];
                        if(
                            $has_role === false &&
                            $permission->getName() === $controller . '.' . $action
                        ){
                            $has_permission = true;
                            $has_role = $role;
                        }
                    }
                    $user_roles[] = $user_role;
                }
                $session = $user->session($object);
                $session['roles'] = $user_roles;
                $object->session('user', $session);
            }
            if(
                $has_permission &&
                $has_role
            ){
                return $has_role;
            }
        }
        throw new AuthorizationException('You don\'t have permission to access this resource. (' . $controller . '.' . $action . ')');
//        throw new ErrorException('Need permission ('. $controller .'.' . $action .')...');
    }

    /**
     * @throws ObjectException
     * @throws ORMException
     * @throws AuthorizationException
     * @throws FileWriteException
     * @throws Exception
     */
    public static function request(App $object, $entity=null, $action='', &$user=null, &$fetchJoinCollection=null): array
    {
        $roles = Permission::getAccessControl($object, $entity, $action);
        $user = User::getByAuthorization($object);
        if($user){
            $session = $object->session('user');
            if($session){
                $roles = $object->session('user.roles');
            }
        }
        if(empty($roles)){
            $roles = $user->getRolesByRank('asc');
        }
        $has_permission = false;
        $request = [];
        $required_attribute = [];
        $explode = explode('.', $entity);
        $explode = array_reverse($explode);
        $alternate = implode('.', $explode);
        foreach($roles as $role){
            if(
                is_array($role) &&
                array_key_exists('permissions', $role)
            ){
                $permissions = $role['permissions'];
            } else {
                $permissions = $role->getPermissions();
            }
            foreach($permissions as $permission){
                if(
                    is_array($permission) &&
                    array_key_exists('name', $permission)
                ){
                    $name = $permission['name'];
                } else {
                    $name = $permission->getName();
                }
                if(
                    (
                        $name === $entity . '.' . $action &&
                        $has_permission === false
                    ) ||
                    (
                        $name === $alternate . '.' . $action &&
                        $has_permission === false
                    )
                ){
                    $has_permission = true;
                    $fetchJoinCollection = true;
                    if(
                        is_array($permission) &&
                        array_key_exists('attributes', $permission)
                    ){
                        $attributes = $permission['attributes'];
                    } else {
                        $attributes = $permission->getAttributes();
                    }
                    if (
                        !empty($attributes) &&
                        is_array($attributes)
                    ) {
                        foreach ($attributes as $attribute) {
                            $assertion = $attribute;
                            $explode = explode(':', $attribute, 2);
                            $compare = null;
                            if (array_key_exists(1, $explode)) {
                                $compare = $explode[1];
                                $attribute = $explode[0];
                                $is_optional = false;
                                if(substr($attribute,0, 1) === '?'){
                                    $is_optional = true;
                                    $attribute = substr($attribute, 1);
                                } else {
                                    $required_attribute[] = $attribute;
                                }
                                if ($compare) {
                                    $parse = new Parse($object, $object->data());
                                    $compare = $parse->compile($compare, $object->data());
                                    $value = Main::castValue($object->request($attribute));
                                    if($is_optional){
                                        if(
                                            $value &&
                                            $value === $compare
                                        ){
                                            $request[$attribute] = $compare;
                                        }
                                        if(
                                            $value &&
                                            $value !== $compare
                                        ){
                                            throw new Exception('Assertion failed: ' . $assertion);
                                        }
                                    } else {
                                        if ($value !== $compare) {
                                            throw new Exception('Assertion failed: ' . $assertion);
                                        }
                                        $request[$attribute] = $compare;
                                    }

                                }
                            } else {
                                $is_optional = false;
                                if(substr($attribute,0, 1) === '?'){
                                    $is_optional = true;
                                    $attribute = substr($attribute, 1);
                                } else {
                                    $required_attribute[] = $attribute;
                                }
                                $value = $object->request($attribute);
                                if($is_optional){
                                    if($value){
                                        $request[$attribute] = $value;
                                    }
                                } else{
                                    $request[$attribute] = $value;
                                }
                            }
                        }
                        break 2;
                    }
                }
            }
        }
        if(empty($has_permission)){
            if($object->config('project.log.security')){
                $object->logger($object->config('project.log.security'))->info('You don\'t have permission to access this resource. (' . $entity . '.' . $action . ')');
            }
            elseif($object->config('project.log.name')){
                $object->logger($object->config('project.log.name'))->info('You don\'t have permission to access this resource. (' . $entity . '.' . $action . ')');
            }
            throw new AuthorizationException('You don\'t have permission to access this resource. (' . $entity . '.' . $action . ')');
        }
        $missing_attribute = [];
        foreach($required_attribute as $attribute){
            $value = $object->request($attribute);
            if($value === null){
                $missing_attribute[] = $attribute;
            }
        }
        if(!empty($missing_attribute)){
            throw new Exception('Method requires attributes [' . implode(', ', $missing_attribute) . '].');
        }
        foreach($request as $attribute => $value){
            $object->request($attribute, $value);
        }
        return $request;
    }
}