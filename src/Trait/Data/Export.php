<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\File;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;


Trait Export {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function export($class, $role, $options=[]){
        if(!array_key_exists('url', $options)){
            return;
        }
        if(File::exist($options['url'])){
            return;
        }
        $name = Controller::name($class);
        $object = $this->object();
        $meta_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $meta = $object->data_read($meta_url);
        ddd($meta);


        $list_options = [];

        $list = $this->list($class, $role, $list_options);


        d($class);
        d($role);
        ddd($options);

        $object = $this->object();
        $data = $object->data_read($options['url']);

        /*
        if($data){
            $create_many = $this->create_many($class, $data);
            ddd($create_many);
        }
        */
    }
}
