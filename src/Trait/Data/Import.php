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

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Import {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    public function import($class, $role, $options=[]): void
    {
        if(!array_key_exists('url', $options)){
            return;
        }
        if(!File::exist($options['url'])){
            return;
        }
        $options['function'] = __FUNCTION__;
        $object = $this->object();
        $app_options = App::options($object);
        $dir = new Dir();
        $read = $dir->read($options['url']);
        $select = [];
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
                    return;
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
                                    //trigger error
                                    $data = new Storage();
                                }
                            }
                        break;
                        case 'json' :
                            $data = $object->data_read($file->url);
                        break;
                    }
                    //we can start import
                    $result = [];
                    if($data){
                        foreach($data->data($class) as $key => $record){
                            $uuid = false;
                            if(
                                is_array($record) &&
                                array_key_exists('uuid', $record)
                            ){
                                $uuid = $record['uuid'];
                            }
                            elseif(
                                is_object($record) &&
                                property_exists($record, 'uuid')
                            ){
                                $uuid = $record->uuid;
                            }
                            d($uuid);
                            if($uuid){
                                $response = $this->read($class, $role, ['uuid' => $uuid]);
                                if(!$response){
                                    //create
                                    $create = $this->create($class, $role, $record, $options);
                                    if(array_key_exists('error', $create)){
                                        $result[$uuid] = $create['error'];
                                    } else {
                                        $result[$uuid] = true;
                                    }
                                } else {
                                    $put = $this->put($class, $role, (array) $record);
                                    ddd($put);
                                    //put
                                }
                            }
                        }
                    }

                    ddd($result);
                }
            }
        }

//        $data = new Storage($read);







        ddd($read);



        $data = $object->data_read($options['url']);

        if($data){
            $create_many = $this->create_many($class, $data);
            ddd($create_many);
        }
    }
}
