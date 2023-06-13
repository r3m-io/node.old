<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Cli;
use R3m\Io\Module\Controller;
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

        $dir = new Dir();
        $read = $dir->read($options['url']);
        if($read){
            $read = Sort::list($read)->with(['url'=> 'desc']);
            $counter = 1;
            foreach($read as $file){
                echo '[' . $counter . '] ' . $file->name() . PHP_EOL;
                $counter++;
            }
            $number = (int) Cli::read('input', 'Please give the number which you want to import: ');
            ddd($number);
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
