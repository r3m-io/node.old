<?php

namespace R3m\Io\Node\Trait;

use Exception;
use R3m\Io\App;
use R3m\Io\Config;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Core;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Parse;
use R3m\Io\Module\Sort;
use stdClass;

Trait Data {


    public function create($class='', $options=[]): void
    {
        d($class);
        d($options);
        ddd('end');
    }

}