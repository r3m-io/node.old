<?php

namespace R3m\Io\Node\Controller;

use Host\Api\Workandtravel\World\Service\Entity;
use R3m\Io\App;


use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Response;
use R3m\Io\Node\Model\Node as Model;

use Exception;

use R3m\Io\Exception\ObjectException;
use R3m\Io\Exception\FileWriteException;

class Node extends Controller {
    const DIR = __DIR__ . '/';

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public static function list(App $object): Response
    {
        $model = new Model($object);

        $sort = $object->request('sort');
        if(empty($sort)){
            $sort = [
                'uuid' => 'ASC'
            ];
        }
        $parse = $object->request('parse');
        if(empty($parse)){
            $parse = false;
        }
        $relation = $object->request('relation');
        if(
            empty($relation) &&
            $relation !== false
        ){
            $relation = true;
        }
        $filter = Node::filter($object, $where, $parameters);
        d($object->request());
        d($where);
        d($parameters);
        ddd($filter);
        $response = $model->list(
            $object->request('class'),
            $model->role_system(), //leak
            [
                'sort' => $sort,
                'filter' => $filter,
                'where' => $where,
                'limit' => (int) $object->request('limit'),
                'page' => (int) $object->request('page'),
                'relation' => (bool) $relation,
                'parse' => (bool) $parse
            ]
        );
        return new Response(
            $response,
            Response::TYPE_JSON
        );
    }

    /**
     * @throws \ReflectionException
     */
    protected static function filter(App $object, &$where=[], &$parameters=[]){
        $request = clone $object->request();
        unset($request->limit);
        unset($request->pagination);
        unset($request->page);
        unset($request->sort);
        unset($request->request);
        unset($request->class);
        unset($request->authorization);
        unset($request->parse);
        unset($request->relation);
        $alias = lcfirst($object->request('class'));
        $filter = $request;
        $where = [];
        $parameters = [];
        foreach($request as $attribute => $array){
            if(substr($attribute, 0, 1) === '@'){
                $attribute = substr($attribute, 1);
            }
            $is_not = false;
            if(is_object($array)){
                $array = Core::object_array($array);
            }
            if(Core::is_array_nested($array)){
                if(array_key_exists('not', $array)){
                    $is_not = true;
                    $array = $array['not'];
                }
            }
            $array = Node::cast_value($array);
            d($array);
            if(is_array($array)){
                if(count($array) > 1){
                    foreach($array as $key => $value){
                        if($key === 'gte') {
                            $where[] = $alias . '.' . $attribute .' >= :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                            unset($array[$key]);
                        }
                        elseif($key === 'lte') {
                            $where[] = $alias . '.' . $attribute .' <= :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                            unset($array[$key]);
                        }
                        elseif($key === 'gt') {
                            $where[] = $alias . '.' . $attribute .' > :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                            unset($array[$key]);
                        }
                        elseif($key === 'lt') {
                            $where[] = $alias . '.' . $attribute .' < :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                            unset($array[$key]);
                        }
                    }
                    if(!empty($array)){
                        if($is_not){
                            $where[] = $alias . '.' . $attribute . ' NOT IN (:' . $attribute . ')';
                        } else {
                            $where[] = $alias . '.' . $attribute . ' IN (:' . $attribute . ')';
                        }
                        $parameters[$attribute] = $array;
                    }
                } else {
                    foreach($array as $key => $value){
                        if(is_numeric($key)){
                            if($value === null){
                                $where[] = $alias . '.' . $attribute . ' IS NULL';
                            }
                            elseif(is_array($value)){
                                $where[] = $alias . '.' . $attribute . ' IN (:' . $attribute . ')';
                                $parameters[$attribute] = $value;
                            } else {
                                $where[] = $alias . '.' . $attribute . ' = :' . $attribute;
                                $parameters[$attribute] = $value;
                            }
                        }
                        elseif($key === 'not'){
                            if($value === null) {
                                $where[] = $alias . '.' . $attribute . ' IS NOT NULL';
                            }
                            elseif(is_array($value)){
                                $where[] = $alias . '.' . $attribute . ' NOT IN (:' . $attribute . ')';
                                $parameters[$attribute] = $value;
                            } else {
                                $where[] = $alias . '.' . $attribute . ' != :' . $attribute;
                                $parameters[$attribute] = $value;
                            }
                        }
                        elseif($key === 'strictly-exact'){
                            d($alias);
                            d($attribute);
                            ddd($is_not);
                            if($is_not){
                                $where[] = $alias . '.' . $attribute . ' != :' . $attribute . '_' . $key;
                            } else {
                                $where[] = $alias . '.' . $attribute . ' = :' . $attribute . '_' . $key;
                            }
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'exact'){
                            if($is_not){
                                $where[] = $alias . '.' . $attribute . ' != :' . $attribute . '_' . $key;
                            } else {
                                $where[] = $alias . '.' . $attribute . ' = :' . $attribute . '_' . $key;
                            }
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'partial'){
                            if($is_not){
                                $where[] = $alias . '.' . $attribute .' NOT LIKE :' . $attribute . '_' . $key;
                            } else {
                                $where[] = $alias . '.' . $attribute .' LIKE :' . $attribute . '_' . $key;
                            }

                            $parameters[$attribute . '_' . $key] = '%' . $value . '%';
                        }
                        elseif($key === 'start'){
                            if($is_not){
                                $where[] = $alias . '.' . $attribute .' NOT LIKE :' . $attribute . '_' . $key;
                            } else {
                                $where[] = $alias . '.' . $attribute . ' LIKE :' . $attribute . '_' . $key;
                            }
                            $parameters[$attribute . '_' . $key] = $value . '%';
                        }
                        elseif($key === 'end'){
                            if($is_not){
                                $where[] = $alias . '.' . $attribute .' NOT LIKE :' . $attribute . '_' . $key;
                            } else {
                                $where[] = $alias . '.' . $attribute . ' LIKE :' . $attribute . '_' . $key;
                            }
                            $parameters[$attribute . '_' . $key] = '%' . $value;
                        }
                        elseif($key === 'gte') {
                            $where[] = $alias . '.' . $attribute .' >= :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'lte') {
                            $where[] = $alias . '.' . $attribute .' <= :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'gt') {
                            $where[] = $alias . '.' . $attribute .' > :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'lt') {
                            $where[] = $alias . '.' . $attribute .' < :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'after'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $where[] = $alias . '.' . $attribute .' >= :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'before'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $where[] = $alias . '.' . $attribute .' <= :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'strictly_after'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $where[] = $alias . '.' . $attribute .' > :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;

                        }
                        elseif($key === 'strictly_before'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $where[] = $alias . '.' . $attribute .' < :' . $attribute . '_' . $key;
                            $parameters[$attribute . '_' . $key] = $value;
                        }
                        elseif($key === 'between'){
                            $value = explode('..', $value, 2);
                            if(array_key_exists(1, $value)){
                                if(is_numeric($value[0])){
                                    $value[0] += 0;
                                }
                                if(is_numeric($value[1])){
                                    $value[1] += 0;
                                }
                                $where[] = $alias . '.' . $attribute .' > :' . $attribute . '_' . $key . '_' . 'gt';
                                $parameters[$attribute . '_' . $key. '_' . 'gt'] = $value[0];
                                if(is_numeric($value)){
                                    $value += 0;
                                }
                                $where[] = $alias . '.' . $attribute .' < :' . $attribute . '_' . $key . '_' . 'lt';
                                $parameters[$attribute . '_' . $key . '_' . 'lt'] = $value[1];
                            }
                        }
                        elseif($key === 'between-equals'){
                            $value = explode('..', $value, 2);
                            if(array_key_exists(1, $value)){
                                if(is_numeric($value[0])){
                                    $value[0] += 0;
                                }
                                if(is_numeric($value[1])){
                                    $value[1] += 0;
                                }
                                $where[] = $alias . '.' . $attribute .' >= :' . $attribute . '_' . $key . '_' . 'gte';
                                $parameters[$attribute . '_' . $key. '_' . 'gte'] = $value[0];
                                if(is_numeric($value)){
                                    $value += 0;
                                }
                                $where[] = $alias . '.' . $attribute .' <= :' . $attribute . '_' . $key . '_' . 'lte';
                                $parameters[$attribute . '_' . $key . '_' . 'lte'] = $value[1];
                            }
                        }
                    }
                }
            } else {
                $value = $array;
                if($value === null){
                    $where[] = $alias . '.' . $attribute . ' IS NULL';
                }
                elseif(is_array($value)){
                    $where[] = $alias . '.' . $attribute . ' IN (:' . $attribute . ')';
                    $parameters[$attribute] = $value;
                }
                elseif($alias) {
                    $where[] = $alias . '.' . $attribute . ' = :' . $attribute;
                    $parameters[$attribute] = $value;
                }
            }
        }
        return $filter;
    }

    protected static function cast_value($array=[]){
        if(is_array($array)){
            foreach($array as $key => $value) {
                if(is_object($value) || is_array($value)){
                    $array[$key] = Node::cast_value($value);
                } else {
                    if($value === 'null'){
                        $array[$key] = null;
                    }
                    elseif($value === 'true'){
                        $array[$key] = true;
                    }
                    elseif($value === 'false'){
                        $array[$key] = false;
                    }
                    elseif(is_numeric($value)){
                        $array[$key] = $value + 0;
                    }
                    elseif(substr($value, 0, 1) === '[' && substr($value, -1, 1) === ']'){
                        $array[$key] = Core::object($value, Core::OBJECT_ARRAY);
                    }
                }
            }
            return $array;
        }
        elseif(is_object($array)){
            foreach($array as $key => $value) {
                if(is_object($value) || is_array($value)){
                    $array->$key = Node::cast_value($value);
                } else {
                    if($value === 'null'){
                        $array->$key = null;
                    }
                    elseif($value === 'true'){
                        $array->$key = true;
                    }
                    elseif($value === 'false'){
                        $array->$key = false;
                    }
                    elseif(is_numeric($value)){
                        $array->$key = $value + 0;
                    }
                    elseif(substr($value, 0, 1) === '[' && substr($value, -1, 1) === ']'){
                        $array->$key = Core::object($value, Core::OBJECT_ARRAY);
                    }
                }
            }
            return $array;
        }
        elseif($array === 'null'){
            return null;
        }
        elseif($array === 'true'){
            return true;
        }
        elseif($array === 'false'){
            return false;
        }
        elseif(is_numeric($array)){
            return $array + 0;
        }
        elseif(substr($array, 0, 1) === '[' && substr($array, -1, 1) === ']'){
            return Core::object($array, Core::OBJECT_ARRAY);
        }
        else {
            return $array;
        }
    }

}