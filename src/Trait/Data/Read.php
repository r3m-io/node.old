<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Core;

Trait Read {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function read($class='', $options=[]): false|array|object
    {
//        $name = Controller::name($class);
        $options = Core::object($options, Core::OBJECT_ARRAY);
        $one = [
            'sort' => [
                'uuid' => 'asc'
            ],
            'filter' => [
                "uuid" => [
                    'operator' => '===',
                    'value' => $options['uuid'],
                ]
            ]
        ];
        d($class);
        d($one);
        $data = $this->one($class, $one);
        ddd($data);
        if($data){
            return $data->data();
        }
        return false;
    }
}