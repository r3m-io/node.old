<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Sort;

use stdClass;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

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
//        File::move($from_dir_binary_tree, $to_dir_binary_tree, true);
        if(File::exist($from_url_expose)){
            $data = $object->data_read($from_url_expose);
            if($data){
                $expose = $data->get($options->from);
                if($expose){
                    $storage = new Storage();
                    $storage->set($options->to, $expose);
//                    $storage->write($to_url_expose);
//                    File::delete($from_url_expose);
                }
            }
        }
//        File::move($from_dir_filter, $to_dir_filter, true);
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
//            ddd($storage);
//            $storage->write($to_url_meta);
//            File::delete($from_url_meta);
        }
//        File::move($from_url_object, $to_url_object, true);
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

//            ddd($storage);
//            $storage->write($to_url_validate);
//            File::delete($from_url_validate);
        }
//        File::move($from_dir_where, $to_dir_where, true);
        $dir_node = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds');
        $dir_binary_tree = $dir_node .
            'BinaryTree' .
            $object->config('ds');
        ;
        $dir_binary_tree_class = $dir_binary_tree .
            $options->to .
            $object->config('ds')
        ;
        $dir_binary_tree_sort = $dir_binary_tree_class .
            'Asc' .
            $object->config('ds')
        ;

        $url = $dir_binary_tree_sort .
            'Uuid' .
            $object->config('extension.btree');
        if(!File::exist($url)){
            //logger error url not found
        }
        $mtime = File::mtime($url);
        $data = File::read($url, File::ARRAY);
        ddd($data);


        ddd($meta);
        /*
        $sort = [
            ''
        ]
*/


        ddd($options);
    }
}