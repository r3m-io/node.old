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
        );
    }

}