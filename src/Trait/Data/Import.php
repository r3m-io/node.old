<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\App;

use R3m\Io\Module\Cli;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Sort;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Import {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function import($class, $role, $options=[]): array
    {
        if(!array_key_exists('url', $options)){
            return [];
        }
        if(!File::exist($options['url'])){
            return [];
        }
        $start = 0x0000;
        $end = 0x10FFFF;
        $table = [];
        $index = 0;
        for($char = $start; $char <= $end; $char++){
            $entity = '&#' . $char . ';';
            $record = [];
            $record['char'] = mb_convert_encoding($entity, 'UTF-8', 'HTML-ENTITIES');
            $encoding = mb_detect_encoding($record['char'], mb_detect_order(), true);
            if($encoding !== 'UTF-8'){
                $record['char'] = iconv($encoding, 'UTF-8//IGNORE', $record['char']);
                $record['is'] = [
                    'broken' => true,
                ];
            }
            $record['entity'] = $entity;
            $record['#index'] = $index;
            $record['#class'] = 'UTF-8';
            $table[$index] = $record;
            $index++;
        }
        $object = $this->object();
        $url = $object->config('project.dir.data') .
            'App' .
            $object->config('ds') .
            'UTF-8' .
            $object->config('extension.json')
        ;
        $storage = new Storage();
        $storage->set('UTF-8', $table);
        $storage->write($url);
        ddd('end');

        $options['function'] = __FUNCTION__;
        $object = $this->object();
        $app_options = App::options($object);
        $dir = new Dir();
        $read = $dir->read($options['url']);
        $select = [];
        $index = 0;
        $result = [
            'list' => [],
            'count' => 0,
            'error' => [
                'list' => [],
                'count' => 0
            ]
        ];
        if($read){
            $read = Sort::list($read)->with(['url'=> 'desc']);
            $counter = 1;
            foreach($read as $file){
                if(
                    property_exists($file, 'name') &&
                    property_exists($file, 'url')
                ){
                    if(!property_exists($app_options, 'number')){
                        echo '[' . $counter . '] ' . $file->name . PHP_EOL;
                    }
                    $select[$counter] = $file->url;
                    $counter++;
                }
            }
            if(property_exists($app_options, 'number')){
                $number = $app_options->number;
                if(!array_key_exists($number, $select)){
                    return [];
                }
            } else {
                $number = (int) Cli::read('input', 'Please give the number which you want to import: ');
                while(
                !array_key_exists($number, $select)
                ){
                    echo 'Invalid input please select a number from the list.' . PHP_EOL;
                    $number = (int) Cli::read('input', 'Please give the number which you want to import: ');
                }
            }
            $read = $dir->read($select[$number], true);
            if($read){
                $read = Sort::list($read)->with(['url'=> 'asc']); //start with page 1
                foreach($read as $file){
                    $file->extension = File::extension($file->name);
                    $data = false;
                    switch($file->extension){
                        case 'gz' :
                            $data = gzdecode(File::read($file->url));
                            if($data){
                                $data = Core::object($data, Core::OBJECT_OBJECT);
                                if($data){
                                    $data = new Storage($data);
                                } else {
                                    throw new Exception('Could not read data from file: ' . $file->url);
                                }
                            }
                        break;
                        case 'json' :
                            $data = $object->data_read($file->url);
                        break;
                    }
                    $create_many = [];
                    $put_many = [];
                    if($data) {
                        foreach ($data->data($class) as $key => $record) {
                            $uuid = false;
                            if (
                                is_array($record) &&
                                array_key_exists('uuid', $record)
                            ) {
                                $uuid = $record['uuid'];
                            } elseif (
                                is_object($record) &&
                                property_exists($record, 'uuid')
                            ) {
                                $uuid = $record->uuid;
                            }
                            if ($uuid) {
                                $response = $this->read($class, $role, ['uuid' => $uuid]);
                                if (!$response) {
                                    $create_many[] = $record;
                                    /*
                                    $create = $this->create($class, $role, $record, $options);
                                    if(array_key_exists('error', $create)){
                                        $result[$uuid] = $create['error'];
                                    } else {
                                        $result[$uuid] = true;
                                    }
                                    */
                                } else {
                                    $put_many[] = $record;
                                    /*
                                    $put = $this->put($class, $role, (array) $record);
                                    if(array_key_exists('error', $put)){
                                        $result[$uuid] = $put['error'];
                                    } else {
                                        $result[$uuid] = true;
                                    }
                                    */
                                }
                            }
                        }
                        $create_many_response = $this->create_many($class, $role, $create_many, $options);
                        $put_options = $options;
                        $put_options['ramdisk'] = true;
                        $put_many_response = $this->put_many($class, $role, $put_many, $put_options);
                        foreach ($create_many_response['list'] as $nr => $record) {
                            $record['#index'] = $index;
                            $result['list'][] = $record;
                            $index++;
                        }
                        foreach ($put_many_response['list'] as $nr => $record) {
                            $record['#index'] = $index;
                            $result['list'][] = $record;
                            $index++;
                        }
                        $result['count']+= $create_many_response['count'];
                        $result['count']+= $put_many_response['count'];
                        if(array_key_exists('error', $create_many_response)){
                            foreach ($create_many_response['error']['list'] as $nr => $record) {
                                $result['error']['list'][] = $record;
                            }
                            $result['error']['count']+= $create_many_response['error']['count'];
                        }
                        if(array_key_exists('error', $put_many_response)) {
                            foreach ($put_many_response['error']['list'] as $nr => $record) {
                                $result['error']['list'][] = $record;
                            }
                            $result['error']['count']+= $put_many_response['error']['count'];
                        }
                    }
                }
            }
        }
        if($result['error']['count'] === 0){
            unset($result['error']);
        }
        return $result;
    }
}
