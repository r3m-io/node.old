<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Data {


    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function create($class='', $options=[]): void
    {
        $name = Controller::name($class);
        $object = $this->object();
        $record = new Storage(Core::object($options, Core::OBJECT_OBJECT));
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $url = $dir_class . 'Data.json';
        $data = $object->data_read($url);
        if(!$data){
            $data = new Storage();
            Dir::create($dir_class, Dir::CHMOD);
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 777 ' . $dir_node;
                exec($command);
                $command = 'chmod 777 ' . $dir_class;
                exec($command);
                if($object->config(Config::POSIX_ID) === 0){
                    $command = 'chown www-data:www-data ' . $dir_node . ' -R';
                    exec($command);
                }
            }
        }
        $record->set('uuid', Core::uuid());
        ddd($record);
        $list = $data->get($class);
        if(empty($list)){
            $list = [];
        }
        $list[] = $record->data();
        $data->set($class, $list);
        $data->write($url);
        if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
            $command = 'chmod 666 ' . $url;
            exec($command);
        }
        if($object->config(Config::POSIX_ID) === 0){
            $command = 'chown www-data:www-data ' . $url;
            exec($command);
        }
    }

}