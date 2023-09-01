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
        d($options);
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
            'multiple' => true,
            'parse' => $options['parse'] ?? false,
        ];
        $ramdisk_record = $object->config('package.r3m_io/node.ramdisk');
        if(empty($ramdisk_record)){
            $ramdisk_record = [];
        }
        if(in_array(
            $name,
            $ramdisk_record,
            true
        )){
            $options_record['ramdisk'] = true;
        }
        $data = $this->record($name, $role, $options_record);
        ddd($data);
        if($data){
            return $data;
        }
        return false;
    }
}