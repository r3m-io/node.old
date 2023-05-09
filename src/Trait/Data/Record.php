<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Record {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function record($class='', $options=[]){
        $options['limit'] = 1;
        $options['page'] = 1;


        $list = $this->list($class, $options);
        if(
            is_array($list) &&
            array_key_exists('list', $list) &&
            array_key_exists(0, $list['list'])
        ){
            return $list['list'][0];
        }
        return null;
    }

}