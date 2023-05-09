<?php

namespace R3m\Io\Node\Trait;

//use R3m\Io\Module\Filter;

use SplFileObject;
use stdClass;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Controller;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Dir;
use R3m\Io\Module\Event;
use R3m\Io\Module\File;
use R3m\Io\Module\Sort;
use R3m\Io\Module\Validate;
use R3m\Io\Module\Parse;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait User {

    public function hasRole($node, $role){

    }

    public function hasPermission($node, $role, $permission){

    }
}