<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Read {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function read($class, $role, $options=[]): false|array|object
    {
        $object = $this->object();
        $name = Controller::name($class);
        $options = Core::object($options, Core::OBJECT_ARRAY);
        if(!array_key_exists('uuid', $options)){
            return false;
        }
        $options_record = [
            'sort' => [
                'uuid' => 'asc'
            ],
            'filter' => [
                "uuid" => [
                    'operator' => '===',
                    'value' => $options['uuid'],
                ]
            ],
            'function' => __FUNCTION__,
            'multiple' => true
        ];
        $ramdisk = $object->data('package.r3m-io/node.ramdisk');
        d($ramdisk);
        if(empty($ramdisk)){
            $ramdisk = [];
        }
        if(in_array(
            $name,
            $ramdisk,
            true
        )){
            $options_record['ramdisk'] = true;
        }
        d($options_record);
        $data = $this->record($name, $role, $options_record);
        if($data){
            return $data;
        }
        return false;
    }
}