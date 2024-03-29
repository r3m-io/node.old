<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;

use R3m\Io\Node\Service\Security;

use Exception;

Trait Rename {

    /**
     * @throws Exception
     */
    public function rename(): void
    {
        $object = $this->object();
        $options = App::options($object);
        if(property_exists($options, 'from')){
            $options->from = Controller::name(trim($options->from));
        } else {
            throw new Exception('Option from is missing');
        }
        if(property_exists($options, 'to')){
            $options->to = Controller::name(trim($options->to));
        } else {
            throw new Exception('Option to is missing');
        }
        $role = $this->role_system();
        if(!property_exists($options, 'function')){
            $options->function = 'rename';
        }
        if(!Security::is_granted(
            $options->from,
            $role,
            Core::object($options, Core::OBJECT_ARRAY)
        )){
            return;
        }
        $from_dir_binary_tree = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinaryTree' .
            $object->config('ds') .
            $options->from .
            $object->config('ds')
        ;
        $to_dir_binary_tree = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'BinaryTree' .
            $object->config('ds') .
            $options->to .
            $object->config('ds')
        ;
        $from_url_expose = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Expose' .
            $object->config('ds') .
            $options->from .
            $object->config('extension.json')
        ;
        $to_url_expose = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Expose' .
            $object->config('ds') .
            $options->to .
            $object->config('extension.json')
        ;
        if($from_dir_binary_tree === $to_dir_binary_tree){
            throw new Exception('From and to are the same');
        }
        if(!Dir::is($from_dir_binary_tree)){
            throw new Exception('From does not exist');
        }
        if(Dir::is($to_dir_binary_tree)){
            throw new Exception('To already exists');
        }
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds');
        $dir_binary_tree = $dir_node .
            'BinaryTree' .
            $object->config('ds');
        ;
        $dir_binary_tree_class = $dir_binary_tree .
            $options->from .
            $object->config('ds')
        ;
        $dir_binary_tree_sort = $dir_binary_tree_class .
            'Asc' .
            $object->config('ds')
        ;
        $url_binary_tree_sort = $dir_binary_tree_sort .
            'Uuid' .
            $object->config('extension.btree');
        if(!File::exist($url_binary_tree_sort)){
            //logger error $url_binary_tree_sort not found
        }
        $mtime = File::mtime($url_binary_tree_sort);
        $data_uuid = File::read($url_binary_tree_sort, File::ARRAY);
        if(is_array($data_uuid)){
            foreach($data_uuid as $uuid){
                $uuid = rtrim($uuid, PHP_EOL);
                $url_node = $object->config('project.dir.data') .
                    'Node' .
                    $object->config('ds') .
                    'Storage' .
                    $object->config('ds') .
                    substr($uuid, 0, 2) .
                    $object->config('ds') .
                    $uuid .
                    $object->config('extension.json')
                ;
                $data_node = $object->data_read($url_node);
                if($data_node){
                    $data_node->set('#class', $options->to);
                    $data_node->write($url_node);
                    if($object->config(Config::POSIX_ID) === 0){
                        $command = 'chown www-data:www-data ' . $url_node;
                        exec($command);
                    }
                    if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                        $command = 'chmod 666 ' . $url_node;
                        exec($command);
                    }
                }
            }
        }
        $from_dir_filter = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Filter' .
            $object->config('ds') .
            $options->from .
            $object->config('ds')
        ;
        $to_dir_filter = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Filter' .
            $object->config('ds') .
            $options->to .
            $object->config('ds')
        ;
        $from_url_meta = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $options->from .
            $object->config('extension.json')
        ;
        $to_url_meta = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $options->to .
            $object->config('extension.json')
        ;
        $from_url_object = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds') .
            $options->from .
            $object->config('extension.json')
        ;
        $to_url_object = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Object' .
            $object->config('ds') .
            $options->to .
            $object->config('extension.json')
        ;
        $from_url_validate = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Validate' .
            $object->config('ds') .
            $options->from .
            $object->config('extension.json')
        ;
        $to_url_validate = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Validate' .
            $object->config('ds') .
            $options->to .
            $object->config('extension.json')
        ;
        $from_dir_where = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Where' .
            $object->config('ds') .
            $options->from .
            $object->config('ds')
        ;
        $to_dir_where = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Where' .
            $object->config('ds') .
            $options->to .
            $object->config('ds')
        ;
        File::move($from_dir_binary_tree, $to_dir_binary_tree, true);
        if($object->config(Config::POSIX_ID) === 0){
            $command = 'chown www-data:www-data ' . $to_dir_binary_tree;
            exec($command);
        }
        if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
            $command = 'chmod 777 ' . $to_dir_binary_tree;
            exec($command);
        }
        if(File::exist($from_url_expose)){
            $data = $object->data_read($from_url_expose);
            if($data){
                $expose = $data->get($options->from);
                if($expose){
                    $expose_data = new Storage();
                    $expose_data->set($options->to, $expose);
                    $expose_data->write($to_url_expose);
                    File::delete($from_url_expose);
                    if($object->config(Config::POSIX_ID) === 0){
                        $command = 'chown www-data:www-data ' . $to_url_expose;
                        exec($command);
                    }
                    if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                        $command = 'chmod 666 ' . $to_url_expose;
                        exec($command);
                    }
                }
            }
        }
        if(File::exist($from_dir_filter)){
            File::move($from_dir_filter, $to_dir_filter, true);
        }

        if($object->config(Config::POSIX_ID) === 0){
            if(File::exist($to_dir_filter)){
                $command = 'chown www-data:www-data ' . $to_dir_filter;
                exec($command);
            }
        }
        if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
            if(File::exist($to_dir_filter)){
                $command = 'chmod 777 ' . $to_dir_filter;
                exec($command);
            }
        }
        if(File::exist($from_url_meta)){
            $read = File::read($from_url_meta);
            $search = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds') .
                'BinaryTree' .
                $object->config('ds') .
                $options->from .
                $object->config('ds')
            ;
            $replace = $object->config('project.dir.data') .
                'Node' .
                $object->config('ds') .
                'BinaryTree' .
                $object->config('ds') .
                $options->to .
                $object->config('ds')
            ;
            $search = str_replace('/', '\/', $search);
            $replace = str_replace('/', '\/', $replace);
            $read = str_replace($search, $replace, $read);
            $search = 'class": "' . $options->from . '"';
            $replace = 'class": "' . $options->to . '"';
            $read = str_replace($search, $replace, $read);
            $search = 'class":"' . $options->from . '"';
            $replace = 'class": "' . $options->to . '"';
            $read = str_replace($search, $replace, $read);
            $data = new Storage();
            $meta = new Storage();
            $data->data(Core::object($read, Core::OBJECT_OBJECT));
            $attributes = [
                'Sort',
                'Filter',
                'Where',
                'Count'
            ];
            foreach($attributes as $attribute){
                if($data->has($attribute . '.' . $options->from)){
                    $get = $data->get($attribute . '.' . $options->from);
                    if($get){
                        $meta->set($attribute . '.' . $options->to, $get);
                    }
                }
            }
            $meta->write($to_url_meta);
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $to_url_meta;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 666 ' . $to_url_meta;
                exec($command);
            }
            File::delete($from_url_meta);
        }
        if(File::exist($from_url_object)) {
            $read = File::read($from_url_object);
            $search = 'class": "' . $options->from . '"';
            $replace = 'class": "' . $options->to . '"';
            $read = str_replace($search, $replace, $read);
            $search = 'class":"' . $options->from . '"';
            $replace = 'class": "' . $options->to . '"';
            $read = str_replace($search, $replace, $read);
            File::write($to_url_object, $read);
            File::delete($from_url_object);
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $to_url_object;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 666 ' . $to_url_object;
                exec($command);
            }
        }
        if(File::exist($from_url_validate)){
            $read = File::read($from_url_validate);
            $search = 'Node' .
                $object->config('ds') .
                'BinaryTree' .
                $object->config('ds') .
                $options->from .
                $object->config('ds')
            ;
            $replace = 'Node' .
                $object->config('ds') .
                'BinaryTree' .
                $object->config('ds') .
                $options->to .
                $object->config('ds')
            ;
            $search = str_replace('/', '\/', $search);
            $replace = str_replace('/', '\/', $replace);
            $read = str_replace($search, $replace, $read);
            $search = 'class": "' . $options->from . '"';
            $replace = 'class": "' . $options->to . '"';
            $read = str_replace($search, $replace, $read);
            $search = 'class":"' . $options->from . '"';
            $replace = 'class": "' . $options->to . '"';
            $read = str_replace($search, $replace, $read);
            $data = new Storage();
            $storage = new Storage();
            $data->data(Core::object($read, Core::OBJECT_OBJECT));
            if($data->has($options->from)) {
                $storage->set($options->to, $data->get($options->from));
            }
            $storage->write($to_url_validate);
            File::delete($from_url_validate);
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $to_url_validate;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 666 ' . $to_url_validate;
                exec($command);
            }
        }
        if(File::exist($from_dir_where)){
            File::move($from_dir_where, $to_dir_where, true);
            if($object->config(Config::POSIX_ID) === 0){
                $command = 'chown www-data:www-data ' . $to_dir_where;
                exec($command);
            }
            if($object->config('framework.environment') === Config::MODE_DEVELOPMENT){
                $command = 'chmod 777 ' . $to_dir_where;
                exec($command);
            }
        }
    }
}