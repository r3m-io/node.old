<?php

namespace R3m\Io\Node\Controller;

use R3m\Io\App;


use R3m\Io\Module\Controller;
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
        $filter = $object->request('filter');
        if(empty($filter)){
            $filter = [];
        }
        elseif(!is_array($filter)){
            throw new Exception('Filter must be an array.');
        }
        $where = $object->request('where');
        if(empty($where)){
            $where = [];
        }
        $parse = $object->request('parse');
        if(empty($parse)){
            $parse = false;
        }
        $relation = $object->request('relation');
        ddd($relation);
        if(
            empty($relation) &&
            $relation !== false
        ){
            $relation = true;
        }
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

}