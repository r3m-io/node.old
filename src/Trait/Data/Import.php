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
        set_time_limit(0);
        $start = microtime(true);
        $options['function'] = __FUNCTION__;
        $object = $this->object();
        $app_options = App::options($object);
        if(property_exists($app_options, 'force')){
            $options['force'] = $app_options->force;
        }
        if(property_exists($app_options, 'offset')){
            $options['offset'] = $app_options->offset;
        }
        if(property_exists($app_options, 'limit')){
            $options['limit'] = $app_options->limit;
        }
        if(
            property_exists($app_options, 'compression') &&
            $app_options->compression === 'gz'
        ){
            $options['compression']['algorithm'] = $app_options->compression;
            $options['compression']['level'] = 9;
        } else {
            $options['compression'] = false;
        }
        if(property_exists($app_options, 'disable-validation')){
            $options['validation'] = false;
        } else {
            $options['validation'] = true;
        }
        if(property_exists($app_options, 'disable-expose')){
            $options['expose'] = false;
        } else {
            $options['expose'] = true;
        }
        if(!array_key_exists('offset', $options)){
            $options['offset'] = 0;
        }
        if(!array_key_exists('limit', $options)){
            $options['limit'] = 50000; //tested on laptop 50.000 is around 80 items / second, above 50.000 is around 5 items / second
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
        $data = $object->data_read($options['url']);
        if($data) {
            $create_many_count = 0;
            $put_many_count = 0;
            $create_many = [];
            $put_many = [];
            $counter = 0;
            $list = $data->data($class);
            if(empty($list)){
                return [];
            }
            $total = count($list);
            if(substr($options['offset'], -1, 1) === '%'){
                $options['offset'] = (int) substr($options['offset'], 0, -1);
                $options['offset'] = (int) ($total * ($options['offset'] / 100));
            }
            if(substr($options['limit'], -1, 1) === '%'){
                $options['limit'] = (int) substr($options['limit'], 0, -1);
                $options['limit'] = (int) ($total * ($options['limit'] / 100));
            }
            foreach ($data->data($class) as $key => $record) {
                if($counter < $options['offset']){
                    $counter++;
                    continue;
                }
                if($counter >= ($options['offset'] + $options['limit'])){
                    break;
                }
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
                $counter++;
            }
            $i = 0;
            $options['transaction'] = true;
            $options['ramdisk'] = true;
            while($i < $create_many_count){
                $temp = array_slice($create_many, $i, 1000, true);
                $length = count($temp);
                $object->logger($object->config('project.log.node'))->info('Count: ' . $length . ' / ' . $create_many_count . ' Start: ' . $i . ' Offset: ' . $options['offset']);
                $create_many_response = $this->create_many($class, $role, $temp, $options);
                ddd($create_many_response);
                foreach ($create_many_response['list'] as $nr => $uuid) {
                    $result['list'][] = $uuid;
                    $index++;
                }
                $duration = microtime(true) - $start;
                $duration_per_item = $duration / $length;
                $item_per_second = 1 / $duration_per_item;
                $object->logger($object->config('project.log.node'))->info('Items (create_many) per second: ' . $item_per_second);
                $start = microtime(true);
                $result['count'] += $create_many_response['count'];
                if(array_key_exists('error', $create_many_response)){
                    foreach ($create_many_response['error']['list'] as $nr => $record) {
                        $result['error']['list'][] = $record;
                    }
                    $result['error']['count'] += $create_many_response['error']['count'];
                }
//                echo 'Create: ' . $i . '/' . $create_many_count . PHP_EOL;
                $i = $i + 1000;
            }
            $i =0;
            $start = microtime(true);
            while($i < $put_many_count){
                $temp = array_slice($put_many, $i, 1000, true);
                $length = count($temp);
                $put_options = $options;
                $put_options['ramdisk'] = true;
                $put_many_response = $this->put_many($class, $role, $temp, $put_options);
                foreach ($put_many_response['list'] as $nr => $record) {
                    $result['list'][] = $record;
                    $index++;
                }
                if($object->config('project.log.node')){
                    $duration = microtime(true) - $start;
                    $duration_per_item = $duration / $length;
                    $item_per_second = 1 / $duration_per_item;
                    $object->logger($object->config('project.log.node'))->info('Items (put_many) per second: ' . $item_per_second);
                    $start = microtime(true);
                }
                $result['count'] += $put_many_response['count'];
                if(array_key_exists('error', $put_many_response)) {
                    foreach ($put_many_response['error']['list'] as $nr => $record) {
                        $result['error']['list'][] = $record;
                    }
                    $result['error']['count'] += $put_many_response['error']['count'];
                }
//                echo 'Update: ' . $i . '/' . $put_many_count . PHP_EOL;
                $i = $i + 1000;
            }
        }
        if($result['error']['count'] === 0){
            unset($result['error']);
        }
        $this->commit($class, $role, $result, $options);
        return $result;
    }
}
