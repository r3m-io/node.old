<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Config;
use R3m\Io\Module\Core;
use R3m\Io\Module\Dir;
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
        $count = $meta->get('Sort.' . $class . '.' . $sort_key . '.' . 'count');
        $page_max = ceil($count / $list_options['limit']);
        for($page=1; $page <= $page_max; $page++){
            $list_options['page'] = $page;
            $response = $this->list($class, $role, $list_options);
            $data = new Storage();
            $list = [];
            foreach($response['list'] as $record){
                $list[] = $record;
            }
            $data->set($name, $list);
            if(array_key_exists('compression', $options)){
                switch(strtolower($options['compression'])){
                    case 'gz':
                        $start = microtime(true);
                        $url = $dir_name . $file_name . '.' . $page . $object->config('extension.json') . $object->config('extension.gz');
                        $data = Core::object($data->data(), Core::OBJECT_JSON);
                        $gz = gzencode($data, 9);
                        Dir::create($dir_name);
                        File::write($url, $gz);
                        $duration = microtime(true) - $start;
                        dd($duration);
                    break;
                }
            } else {
                $url = $dir_name . $file_name . '.' . $page . $object->config('extension.json');
                $data->write($url);
            }

            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $url;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 666 ' . $url;
                exec($command);
            }
        }
        $dir_class = Dir::name($dir_name);
        $dir_node = Dir::name($dir_class);
        $dir_package = Dir::name($dir_node);
        $dir_backup = Dir::name($dir_package);
        $dir_mount = Dir::name($dir_backup);
        if($object->config(Config::POSIX_ID) === 0){
            $command = 'chown www-data:www-data ' . $dir_name;
            exec($command);
            $command = 'chown www-data:www-data ' . $dir_class;
            exec($command);
            $command = 'chown www-data:www-data ' . $dir_node;
            exec($command);
            $command = 'chown www-data:www-data ' . $dir_package;
            exec($command);
            $command = 'chown www-data:www-data ' . $dir_mount;
            exec($command);
        }
        if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
            $command = 'chmod 777 ' . $dir_name;
            exec($command);
            $command = 'chmod 777 ' . $dir_class;
            exec($command);
            $command = 'chmod 777 ' . $dir_node;
            exec($command);
            $command = 'chmod 777 ' . $dir_package;
            exec($command);
            $command = 'chmod 777 ' . $dir_mount;
            exec($command);
        }
    }
}
