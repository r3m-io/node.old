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
        $filter = Node::filter($object);
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
            d($array);
            if(is_array($array)){
                if(count($array) > 1){
                    foreach($array as $key => $value){
                        if($key === 'gte') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '>=',
                                'attribute' => $attribute
                            ];
                            unset($array[$key]);
                        }
                        elseif($key === 'lte') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '<=',
                                'attribute' => $attribute
                            ];
                            unset($array[$key]);
                        }
                        elseif($key === 'gt') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '>',
                                'attribute' => $attribute
                            ];
                            unset($array[$key]);
                        }
                        elseif($key === 'lt') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '<',
                                'attribute' => $attribute
                            ];
                            unset($array[$key]);
                        }
                    }
                    if(!empty($array)){
                        if($is_not){
                            $filter[] = [
                                'value' => $array,
                                'operator' => 'not-in',
                                'attribute' => $attribute
                            ];
                        } else {
                            $filter[] = [
                                'value' => $array,
                                'operator' => 'in',
                                'attribute' => $attribute
                            ];
                        }
                    }
                } else {
                    foreach($array as $key => $value){
                        if(is_numeric($key)){
                            if(is_array($value)){
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'in',
                                    'attribute' => $attribute
                                ];
                            } else {
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => '===',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                        elseif($key === 'not'){
                            if(is_array($value)){
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'not-in',
                                    'attribute' => $attribute
                                ];
                            } else {
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => '!==',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                        elseif($key === 'strictly-exact'){
                            if($is_not){
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => '!==',
                                    'attribute' => $attribute
                                ];
                            } else {
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => '===',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                        elseif($key === 'exact'){
                            if($is_not){
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => '!=',
                                    'attribute' => $attribute
                                ];
                            } else {
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => '==',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                        elseif($key === 'partial'){
                            if($is_not){
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'not-partial',
                                    'attribute' => $attribute
                                ];
                            } else {
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'partial',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                        elseif($key === 'start'){
                            if($is_not){
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'not-start',
                                    'attribute' => $attribute
                                ];
                            } else {
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'start',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                        elseif($key === 'end'){
                            if($is_not){
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'not-end',
                                    'attribute' => $attribute
                                ];
                            } else {
                                $filter[] = [
                                    'value' => $value,
                                    'operator' => 'end',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                        elseif($key === 'gte') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '>=',
                                'attribute' => $attribute
                            ];
                        }
                        elseif($key === 'lte') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '<=',
                                'attribute' => $attribute
                            ];
                        }
                        elseif($key === 'gt') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '>',
                                'attribute' => $attribute
                            ];
                        }
                        elseif($key === 'lt') {
                            $filter[] = [
                                'value' => $value,
                                'operator' => '<',
                                'attribute' => $attribute
                            ];
                        }
                        elseif($key === 'after'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[] = [
                                'value' => $value,
                                'operator' => '>=',
                                'attribute' => $attribute
                            ];
                        }
                        elseif($key === 'before'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[] = [
                                'value' => $value,
                                'operator' => '<=',
                                'attribute' => $attribute
                            ];
                        }
                        elseif($key === 'strictly_after'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[] = [
                                'value' => $value,
                                'operator' => '>',
                                'attribute' => $attribute
                            ];

                        }
                        elseif($key === 'strictly_before'){
                            $value = strtotime($value);
                            $value = date('Y-m-d H:i:s', $value);
                            $filter[] = [
                                'value' => $value,
                                'operator' => '<',
                                'attribute' => $attribute
                            ];
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
                                $filter[] = [
                                    'value' => $value[0],
                                    'operator' => '>',
                                    'attribute' => $attribute
                                ];
                                $filter[] = [
                                    'value' => $value[1],
                                    'operator' => '<',
                                    'attribute' => $attribute
                                ];
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
                                $filter[] = [
                                    'value' => $value[0],
                                    'operator' => '>=',
                                    'attribute' => $attribute
                                ];
                                $filter[] = [
                                    'value' => $value[1],
                                    'operator' => '<=',
                                    'attribute' => $attribute
                                ];
                            }
                        }
                    }
                }
            } else {
                $value = $array;
                if(is_array($value)){
                    $filter[] = [
                        'value' => $value,
                        'operator' => 'in',
                        'attribute' => $attribute
                    ];
                }
                elseif($alias) {
                    $filter[] = [
                        'value' => $value,
                        'operator' => '===',
                        'attribute' => $attribute
                    ];
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