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
        /*
object(stdClass)#426 (2) {
  ["action"]=>
  string(16) "cli.autoload.run"
  ["options"]=>
  object(stdClass)#431 (3) {
    ["command"]=>
    array(0) {
    }
    ["controller"]=>
    array(0) {
    }
    ["priority"]=>
    int(10)
  }
}
         */
    }

}