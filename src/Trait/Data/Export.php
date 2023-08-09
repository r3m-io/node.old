<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Config;
use R3m\Io\Module\Core;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data as Storage;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;


Trait Export {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function export($class, $role, $options=[]){
        if(!array_key_exists('url', $options)){
            return;
        }
        $name = Controller::name($class);
        $object = $this->object();
        $dir_name = Dir::name($options['url']);
        $file_name = File::basename($options['url'], $object->config('extension.json'));
        $meta_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $meta = $object->data_read($meta_url);
        $list_options = [
            'sort' => [
                'uuid' => 'asc'
            ],
            'limit' => $options['limit'] ?? 1000,
        ];
        $properties = [];
        $url_key = 'url.';
        if(!array_key_exists('sort', $list_options)){
            $debug = debug_backtrace(true);
            ddd($debug[0]['file'] . ' ' . $debug[0]['line']);
        }
        foreach($list_options['sort'] as $key => $order) {
            if(empty($properties)){
                $url_key .= 'asc.';
            } else {
                $url_key .= strtolower($order) . '.';
            }
            $properties[] = $key;
        }
        $url_key = substr($url_key, 0, -1);
        $sort_key = [
            'property' => $properties,
        ];
        $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
        $count = $meta->get('Sort.' . $name . '.' . $sort_key . '.' . 'count');
        $page_max = ceil($count / $list_options['limit']);
        $data = new Storage();
        for($page=1; $page <= $page_max; $page++){
            if(
                array_key_exists('page', $options) &&
                $page !== $options['page']
            ){
                continue;
            }
            $list_options['page'] = $page;
            $response = $this->list($name, $role, $list_options);
            $list = [];
            foreach($response['list'] as $record){
                if(property_exists($record, '#index')){
                    unset($record->{'#index'});
                }
                $list[] = $record;
            }
            $data->set($name, $list);
        }
        $url = false;
        if(
            array_key_exists('compression', $options) &&
            is_array($options['compression']) &&
            array_key_exists('algorithm', $options['compression']) &&
            array_key_exists('level', $options['compression'])
        ){
            switch(strtolower($options['compression']['algorithm'])){
                case 'gz':
                    $url = $dir_name . $file_name . $object->config('extension.json') . $object->config('extension.gz');
                    $data = Core::object($data->data(), Core::OBJECT_JSON);
                    $gz = gzencode($data, $options['compression']['level']);
                    Dir::create($dir_name);
                    File::write($url, $gz);
                    break;
            }
        } else {
            $url = $dir_name . $file_name . $object->config('extension.json');
            $data->write($url);
        }
        if($object->config(Config::POSIX_ID) === 0 && $url){
            $command = 'chown www-data:www-data ' . $url;
            exec($command);
        }
        if($object->config('framework.environment') === Config::MODE_DEVELOPMENT && $url){
            $command = 'chmod 666 ' . $url;
            exec($command);
        }
        $dir = $url;
        while(true){
            $dir = Dir::name($dir);
            if(in_array(
                $dir,
                [
                    '/Application/' ,
                    '/'
                ],
                true
                )
            ){
                break;
            }
            if(empty($dir)){
                break;
            }
            if($object->config(Config::POSIX_ID) === 0) {
                $command = 'chown www-data:www-data ' . $dir;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                $command = 'chmod 777 ' . $dir;
                exec($command);
            }
        }
    }
}
