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
        $source = $object->config('controller.dir.data') .
            'Node' .
            $object->config('ds') .
            'Role' .
            $object->config('ds') .
            'Validate.json'
        ;
        $dir_destination = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Role' .
            $object->config('ds')
        ;
        $destination = $dir_destination . 'Validate.json';
        Dir::create($dir_destination, Dir::CHMOD);
        if(File::exist($destination)){
            File::delete($destination);
        }
        File::copy($source, $destination);
    }


}