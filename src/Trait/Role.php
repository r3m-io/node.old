<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\App;
use R3m\Io\Config;

Trait Role {

    public function role_system()
    {
        $object = $this->object();
        if($object->config(Config::POSIX_ID) === 0){
            $url = $object->config('controller.dir.data') . 'Node' . $object->config('ds') . 'Role' . $object->config('ds') . 'System.json';
            $data = $object->data_read($url);
            return $data->data();
        }
    }
}