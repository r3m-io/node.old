<?php
namespace R3m\Io\Node\Service;

use Entity\Role;
use Entity\User as Entity;

use Exception;

use R3m\Io\App;
use R3m\Io\Module\Core;
use R3m\Io\Module\Data;
use R3m\Io\Module\Database;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Module\Handler;
use R3m\Io\Module\Parse;

use R3m\Io\Exception\ErrorException;
use R3m\Io\Exception\AuthorizationException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Exception\FileWriteException;

use Doctrine\ORM\Exception\ORMException;
use stdClass;

class Security extends Main
{

    public static function is_granted($class, $role, $options){
        d($class);
        d($role);
        ddd($options);
    }


}