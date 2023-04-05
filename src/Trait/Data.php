<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\Event;
use R3m\Io\Module\File;
use R3m\Io\Module\Validate;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;


Trait Data {


    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function create($class='', $options=[]): false|array
    {
        $name = Controller::name($class);
        $object = $this->object();
        $node = new Storage( (object) $options);
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
        $node->set('uuid', Core::uuid());
        $list = $data->get($class);
        if(empty($list)){
            $list = [];
        }
        $validate_url =  $dir_class . 'Validate.json';
        $validate = $this->validate($object, $validate_url,  $class . '.create');
        $response = [];
        if($validate) {
            if($validate->success === true) {
                $list[] = $node->data();
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
                Event::trigger($object, 'r3m.io.node.data.create', [
                    'class' => $class,
                    'options' => $options,
                    'url' => $url,
                    'node' => $node,
                ]);
                $function = 'create';
                $expose = $this->getExpose(
                    $object,
                    $class,
                    $class . '.' . $function .'.expose'
                );
                ddd($expose);
                $record = $this->expose(
                    $object,
                    $node,
                    $expose,
                    $class,
                    $function
                );
                $response['node'] = $record;
            } else {
                $response['error'] = $validate->test;
            }
            return $response;
        }
        return false;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    protected function validate(App $object, $url, $type){
        $data = $object->data(sha1($url));
        if($data === null){
            $data = $object->parse_read($url, sha1($url));
        }
        if($data){
            $validate = $data->data($type . '.validate');
            if(empty($validate)){
                return false;
            }
            return Validate::validate($object, $validate);
        }
        return false;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    protected function expose(App $object, Storage $node, $expose, $class, $function){

        $record = $node->data();
        return $record;
    }

    /**
     * @throws ObjectException
     * @throws Exception
     */
    protected function getExpose(App $object, $name='', $attribute=''){
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds')
        ;
        $dir_class = $dir_node .
            $name .
            $object->config('ds')
        ;
        $url = $dir_class . 'Expose.json';
        if(!File::exist($url)){
            throw new Exception('Data url (' . $url . ') not found for class: ' . $name);
        }
        $data = $object->data_read($url);
        if($data){
            $get = $data->get($attribute);
            if(empty($get)){
                throw new Exception('Cannot find attribute (' . $attribute .') in class: ' . $name);
            }
            return $get;
        }
    }
}