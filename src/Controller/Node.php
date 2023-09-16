<?php

namespace R3m\Io\Node\Controller;

use Host\Api\Workandtravel\World\Service\Entity;
use R3m\Io\App;


use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Dir;
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
        $where = $object->request('where');
        if(empty($where)){
            $where = false;
        }
        $relation = $object->request('relation');
        if(
            empty($relation) &&
            $relation !== false
        ){
            $relation = true;
        }
        $limit = (int) $object->request('limit');
        if(empty($limit)){
            $limit = 30;
        }
        $page = (int) $object->request('page');
        if(empty($page)){
            $page = 1;
        }
        $filter = Node::filter($object);
        $response = $model->list(
            $object->request('class'),
            $model->role_system(), //leak
            [
                'sort' => $sort,
                'filter' => $filter,
                'where' => $where,
                'limit' => $limit,
                'page' => $page,
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
    protected static function filter(App $object): array
    {
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
        $filter = [];
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
            if(is_array($array)){
                if(count($array) > 1){
                    foreach($array as $key => $value){
                        if($key === 'gte') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '>=',
                            ];
                            unset($array[$key]);
                        }
                        elseif($key === 'lte') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '<=',
                            ];
                            unset($array[$key]);
                        }
                        elseif($key === 'gt') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '>',
                            ];
                            unset($array[$key]);
                        }
                        elseif($key === 'lt') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '<',
                            ];
                            unset($array[$key]);
                        }
                    }
                    if(!empty($array)){
                        if($is_not){
                            $filter[$attribute] = [
                                'value' => $array,
                                'operator' => 'not-in',
                            ];
                        } else {
                            $filter[$attribute] = [
                                'value' => $array,
                                'operator' => 'in',
                            ];
                        }
                    }
                } else {
                    foreach($array as $key => $value){
                        if(is_numeric($key)){
                            if(is_array($value)){
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'in',
                                ];
                            } else {
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => '===',
                                ];
                            }
                        }
                        elseif($key === 'not'){
                            if(is_array($value)){
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'not-in',
                                ];
                            } else {
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => '!==',
                                ];
                            }
                        }
                        elseif($key === 'strictly-exact'){
                            if($is_not){
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => '!==',
                                ];
                            } else {
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => '===',
                                ];
                            }
                        }
                        elseif($key === 'exact'){
                            if($is_not){
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => '!=',
                                ];
                            } else {
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => '==',
                                ];
                            }
                        }
                        elseif($key === 'partial'){
                            if($is_not){
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'not-partial',
                                ];
                            } else {
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'partial',
                                ];
                            }
                        }
                        elseif($key === 'start'){
                            if($is_not){
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'not-start',
                                ];
                            } else {
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'start',
                                ];
                            }
                        }
                        elseif($key === 'end'){
                            if($is_not){
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'not-end',
                                ];
                            } else {
                                $filter[$attribute] = [
                                    'value' => $value,
                                    'operator' => 'end',
                                ];
                            }
                        }
                        elseif($key === 'gte') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '>=',
                            ];
                        }
                        elseif($key === 'lte') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '<=',
                            ];
                        }
                        elseif($key === 'gt') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '>',
                            ];
                        }
                        elseif($key === 'lt') {
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '<',
                            ];
                        }
                        elseif($key === 'after'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '>=',
                            ];
                        }
                        elseif($key === 'before'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '<=',
                            ];
                        }
                        elseif($key === 'strictly_after'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '>',
                            ];

                        }
                        elseif($key === 'strictly_before'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => '<',
                            ];
                        }
                        elseif($key === 'between'){
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => $key,
                            ];
                        }
                        elseif($key === 'between-equals'){
                            $filter[$attribute] = [
                                'value' => $value,
                                'operator' => $key,
                            ];
                        }
                    }
                }
            } else {
                $value = $array;
                if(is_array($value)){
                    $filter[$attribute] = [
                        'value' => $value,
                        'operator' => 'in',
                    ];
                }
                elseif($alias) {
                    $filter[$attribute] = [
                        'value' => $value,
                        'operator' => '===',
                    ];
                }
            }
        }
        return $filter;
    }

    public static function object_tree(App $object){
        $dir = new Dir();
        $read = $dir->read(
            $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds'),
            false
        );
        $data = [];
        $data['nodeList'] = [];
        $data['nodeList']['tree'] = $read;
        return new Response(
            $data,
            Response::TYPE_JSON
        );return new Response(
            $data,
            Response::TYPE_JSON
        );
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