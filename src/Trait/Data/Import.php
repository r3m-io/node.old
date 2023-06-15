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
        $start = microtime(true);
        $options['function'] = __FUNCTION__;
        $object = $this->object();
        $app_options = App::options($object);
        if(property_exists($app_options, 'force')){
            $options['force'] = $app_options->force;
        }
        $data = false;
        $index = 0;
        $result = [
            'list' => [],
            'count' => 0,
            'error' => [
                'list' => [],
                'count' => 0
            ]
        ];
        if(
            array_key_exists('is_url', $options) &&
            $options['is_url'] === true
        ){
            $data = $object->data_read($options['url']);
        } else {
            $dir = new Dir();
            $read = $dir->read($options['url']);
            $select = [];
            if($read) {
                $read = Sort::list($read)->with(['url' => 'desc']);
                $counter = 1;
                foreach ($read as $file) {
                    if (
                        property_exists($file, 'name') &&
                        property_exists($file, 'url')
                    ) {
                        if (!property_exists($app_options, 'number')) {
                            echo '[' . $counter . '] ' . $file->name . PHP_EOL;
                        }
                        $select[$counter] = $file->url;
                        $counter++;
                    }
                }
                if (property_exists($app_options, 'number')) {
                    $number = $app_options->number;
                    if (!array_key_exists($number, $select)) {
                        return [];
                    }
                } else {
                    $number = (int) Cli::read('input', 'Please give the number which you want to import: ');
                    while (
                    !array_key_exists($number, $select)
                    ) {
                        echo 'Invalid input please select a number from the list.' . PHP_EOL;
                        $number = (int) Cli::read('input', 'Please give the number which you want to import: ');
                    }
                }
                $read = $dir->read($select[$number], true);
                if ($read) {
                    $read = Sort::list($read)->with(['url' => 'asc']); //start with page 1
                    foreach ($read as $file) {
                        $file->extension = File::extension($file->name);
                        $data = false;
                        switch ($file->extension) {
                            case 'gz' :
                                $data = gzdecode(File::read($file->url));
                                if ($data) {
                                    $data = Core::object($data, Core::OBJECT_OBJECT);
                                    if ($data) {
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
                    }
                }
            }
        }
        if($data) {
            $create_many_count = 0;
            $put_many_count = 0;
            $create_many = [];
            $put_many = [];
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
                if(
                    property_exists($app_options, 'is_new') &&
                    $app_options->is_new === true
                ){
                    $uuid = false;
                }
                if ($uuid) {
                    $response = $this->read($class, $role, ['uuid' => $uuid]);
                    if (!$response) {
                        $create_many[] = $record;
                        $create_many_count++;
                    } else {
                        $put_many[] = $record;
                        $put_many_count++;
                    }
                } else {
                    $create_many[] = $record;
                    $create_many_count++;
                }
            }
            $i = 0;
            while($i < $create_many_count){
                $temp = array_slice($create_many, $i, 1000, true);
                echo 'Count: ' . count($temp) . ' Start: ' . $i . PHP_EOL;
                $create_many_response = $this->create_many($class, $role, $temp, $options);
                if($index < 1000){
                    foreach ($create_many_response['list'] as $nr => $record) {
                        if(is_string($record)){
                            ddd($create_many_response);
                        }
                        $record['#index'] = $index;
                        $result['list'][] = $record;
                        $index++;
                    }
                }
                $duration = microtime(true) - $start;
                echo $duration . PHP_EOL;
                $result['count'] += $create_many_response['count'];
                if(array_key_exists('error', $create_many_response)){
                    foreach ($create_many_response['error']['list'] as $nr => $record) {
                        $result['error']['list'][] = $record;
                    }
                    $result['error']['count']+= $create_many_response['error']['count'];
                }
                echo 'Create: ' . $i . '/' . $create_many_count . PHP_EOL;
                $i = $i + 1000;
            }
            $i =0;
            while($i < $put_many_count){
                $temp = array_slice($put_many, $i, 1000, true);
                $put_options = $options;
                $put_options['ramdisk'] = true;
                $put_many_response = $this->put_many($class, $role, $temp, $put_options);
                if($index < 1000){
                    foreach ($put_many_response['list'] as $nr => $record) {
                        $record['#index'] = $index;
                        $result['list'][] = $record;
                        $index++;
                    }
                }
                $result['count'] += $put_many_response['count'];
                if(array_key_exists('error', $put_many_response)) {
                    foreach ($put_many_response['error']['list'] as $nr => $record) {
                        $result['error']['list'][] = $record;
                    }
                    $result['error']['count'] += $put_many_response['error']['count'];
                }
                echo 'Update: ' . $i . '/' . $put_many_count . PHP_EOL;
                $i = $i + 1000;
            }
        }
        if($result['error']['count'] === 0){
            unset($result['error']);
        }
        return $result;
    }
}
