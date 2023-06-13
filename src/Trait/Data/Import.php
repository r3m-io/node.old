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
    public function import($class, $role, $options=[]){
        if(!array_key_exists('url', $options)){
            return;
        }
        if(!File::exist($options['url'])){
            return;
        }
        $object = $this->object();
        $app_options = App::options($object);
ddd($app_options);

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
                    echo '[' . $counter . '] ' . $file->name . PHP_EOL;
                    $select[$counter] = $file->url;
                    $counter++;
                }
            }
            $number = (int) Cli::read('input', 'Please give the number which you want to import: ');
            while(
                !array_key_exists($number, $select)
            ){
                echo 'Invalid input please select a number from the list.' . PHP_EOL;
                $number = (int) Cli::read('input', 'Please give the number which you want to import: ');
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
                    ddd($data);
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
