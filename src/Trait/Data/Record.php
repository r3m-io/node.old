<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Record {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function record($class, $role, $options=[]){
        $options['limit'] = 1;
        $options['page'] = 1;
        $options['function'] = __FUNCTION__;
        if(!array_key_exists('sort', $options)){
            $debug = debug_backtrace(true);
            ddd($debug[0]['file'] . ' ' . $debug[0]['line']);
        }
        $list = $this->list($class, $role, $options);
        if(
            is_array($list) &&
            array_key_exists('list', $list) &&
            array_key_exists(0, $list['list'])
        ){
            $record = $list;
            $record['node'] = $list['list'][0];
            unset($record['list']);
            return $record;
        }
        return null;
    }

}