<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\File;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;


Trait Import {

    public function import($class, $options=[]){
        if(!array_key_exists('url', $options)){
            return;
        }
        if(!File::exist($options['url'])){
            return;
        }
        $object = $this->object();
        $data = $object->data_read($options['url']);

        ddd($data);

        d($class);
        ddd($options);
    }
}
