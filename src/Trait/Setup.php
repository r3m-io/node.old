<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Dir;
use R3m\Io\Module\File;

use Exception;

Trait Setup {

    /**
     * @throws Exception
     */
    public function install(): void
    {
        $this->user();
    }

    /**
     * @throws Exception
     */
    public function user(): void
    {
        $object = $this->object();
        if($object->config(Config::POSIX_ID) !== 0){
            return;
        }
        $classes = [
            'User',
            'Role',
        ];
        foreach ($classes as $class){
            $source = $object->config('controller.dir.data') .
                'Node' .
                $object->config('ds') .
                $class .
                $object->config('ds') .
                'Validate.json'
            ;
            $dir_node = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds')
            ;
            $dir_destination = $dir_node .
                $class .
                $object->config('ds')
            ;
            $destination = $dir_destination . 'Validate.json';
            if(!Dir::is($dir_node)){
                Dir::create($dir_node, Dir::CHMOD);
                $command = 'chown www-data:www-data ' . $dir_node;
                exec($command);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 777 ' . $dir_node;
                    exec($command);
                }
            }
            if(!Dir::is($dir_destination)){
                Dir::create($dir_destination, Dir::CHMOD);
                $command = 'chown www-data:www-data ' . $dir_destination;
                exec($command);
                if($object->config('framework.environment') === Config::MODE_DEVELOPMENT) {
                    $command = 'chmod 777 ' . $dir_destination;
                    exec($command);
                }
            }
            if(File::exist($destination)){
                File::delete($destination);
            }
            File::copy($source, $destination);
            $command = 'chown www-data:www-data ' . $destination;
            exec($command);
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 666 ' . $destination;
                exec($command);
            }
        }
    }
}