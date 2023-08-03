<?php

namespace Package\R3m_io\Node\Controller;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\Core;
use R3m\Io\Module\Controller;
use R3m\Io\Module\Data;
use R3m\Io\Module\Dir;
use R3m\Io\Module\File;

use Exception;

use R3m\Io\Exception\LocateException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Exception\UrlEmptyException;
use R3m\Io\Exception\UrlNotExistException;

class Cli extends Controller {
    const DIR = __DIR__ . '/';
    const MODULE_INFO = 'Info';
    const INFO = [
        '{{binary()}} r3m-io/node                    | Node (Object store) options',
        '{{binary()}} r3m-io/node app                | Node (Object App) options',
        '{{binary()}} r3m-io/node object             | Node (Object Classes) options',
        '{{binary()}} r3m-io/node setup              | Node setup'
    ];

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public static function run(App $object){
        $autoload = [];
        $data = new Data();
        $data->set('prefix', 'Node');
        $data->set('directory', $object->config('project.dir.root') . 'Node/');
        $autoload[] = clone $data->data();
        $data->clear();
        $data->set('autoload', $autoload);
        Cli::autoload($object, $data);
        $node = $object->request(0);
        $scan = Cli::scan($object);
        $category = $object->parameter($object, $node, 1);
        if(!in_array($category, $scan['category'])){
            $category = Cli::MODULE_INFO;
        }
        $module = $object->parameter($object, $node, 2);
        if(!in_array($module, $scan['module'])){
            $module = Cli::MODULE_INFO;
        }
        $submodule = $object->parameter($object, $node, 3);
        if(
            !in_array(
                $submodule,
                $scan['submodule'],
                true
            )
        ){
            $submodule = false;
        }
        $command = $object->parameter($object, $node, 4);
        if(
            !in_array(
                $command,
                $scan['command'],
                true
            ) ||
            $module === Cli::MODULE_INFO ||
            $submodule === Cli::MODULE_INFO
        ){
            $command = false;
        }
        $subcommand = $object->parameter($object, $node, 5);
        if(
            !in_array(
                $subcommand,
                $scan['subcommand'],
                true
            ) ||
            $module === Cli::MODULE_INFO ||
            $submodule === Cli::MODULE_INFO ||
            $command === CLI::MODULE_INFO
        ){
            $subcommand = false;
        }
        $action = $object->parameter($object, $node, 6);
        if(
            !in_array(
                $action,
                $scan['action'],
                true
            ) ||
            $module === Cli::MODULE_INFO ||
            $submodule === Cli::MODULE_INFO ||
            $command === CLI::MODULE_INFO ||
            $subcommand === CLI::MODULE_INFO
        ){
            $action = false;
        }
        $subaction = $object->parameter($object, $node, 7);
        if(
            !in_array(
                $subaction,
                $scan['subaction'],
                true
            ) ||
            $module === Cli::MODULE_INFO ||
            $submodule === Cli::MODULE_INFO ||
            $command === CLI::MODULE_INFO ||
            $subcommand === CLI::MODULE_INFO ||
            $action === CLI::MODULE_INFO
        ){
            $subaction = false;
        }
        try {
            if(
                !empty($module) &&
                !empty($submodule) &&
                !empty($command) &&
                !empty($subcommand) &&
                !empty($action) &&
                !empty($subaction)
            ){
                $url = Cli::locate(
                    $object,
                    ucfirst($category) .
                    '.' .
                    ucfirst($module) .
                    '.' .
                    ucfirst($submodule) .
                    '.' .
                    ucfirst($command) .
                    '.' .
                    ucfirst($subcommand) .
                    '.' .
                    ucfirst($action) .
                    '.' .
                    ucfirst($subaction)
                );
            }
            elseif(
                !empty($module) &&
                !empty($submodule) &&
                !empty($command) &&
                !empty($subcommand) &&
                !empty($action)
            ){
                $url = Cli::locate(
                    $object,
                    ucfirst($category) .
                    '.' .
                    ucfirst($module) .
                    '.' .
                    ucfirst($submodule) .
                    '.' .
                    ucfirst($command) .
                    '.' .
                    ucfirst($subcommand) .
                    '.' .
                    ucfirst($action)
                );
            }
            elseif(
                !empty($module) &&
                !empty($submodule) &&
                !empty($command) &&
                !empty($subcommand)
            ){
                $url = Cli::locate(
                    $object,
                    ucfirst($category) .
                    '.' .
                    ucfirst($module) .
                    '.' .
                    ucfirst($submodule) .
                    '.' .
                    ucfirst($command) .
                    '.' .
                    ucfirst($subcommand)
                );
            }
            elseif(
                !empty($module) &&
                !empty($submodule) &&
                !empty($command)
            ){
                $url = Cli::locate(
                    $object,
                    ucfirst($category) .
                    '.' .
                    ucfirst($module) .
                    '.' .
                    ucfirst($submodule) .
                    '.' .
                    ucfirst($command)
                );
            }
            elseif(
                !empty($module) &&
                !empty($submodule)
            ){
                $url = Cli::locate(
                    $object,
                    ucfirst($category) .
                    '.' .
                    ucfirst($module) .
                    '.' .
                    ucfirst($submodule)
                );
            }
            elseif(!empty($module)){
                $url = Cli::locate(
                    $object,
                    ucfirst($category) .
                    '.' .
                    ucfirst($module)
                );
            }
            else {
                $url = Cli::locate(
                    $object,
                    ucfirst($category)
                );
            }
            return Cli::response($object, $url);
        } catch (Exception | UrlEmptyException | UrlNotExistException | LocateException $exception){
            return $exception;
        }
    }

    private static function scan(App $object): array
    {
        $scan = [
            'category' => [],
            'subcategory' => [],
            'module' => [],
            'submodule' => [],
            'command' => [],
            'subcommand' => [],
            'action' => [],
            'subaction' => []
        ];
        $url = $object->config('controller.dir.view');
        if(!Dir::exist($url)){
            return $scan;
        }
         $dir = new Dir();
        $read = $dir->read($url, true);
        if(!$read){
            return $scan;
        }
        foreach($read as $nr => $file){
            if($file->type !== File::TYPE){
                continue;
            }
            $part = substr($file->url, strlen($url));
            $explode = explode('/', $part, 6);
            if(array_key_exists(0, $explode) === false){
                continue;
            }
            $category = strtolower(File::basename($explode[0], $object->config('extension.tpl')));
            $temp = explode('.', $category, 2);
            if(array_key_exists(1, $temp)){
                $category = $temp[0];
                $subcategory = $temp[1];
            } else {
                $subcategory = false;
            }
            $module = false;
            $submodule = false;
            $command = false;
            $subcommand = false;
            $action = false;
            $subaction = false;
            if(array_key_exists(1, $explode)){
                $module = strtolower(File::basename($explode[1], $object->config('extension.tpl')));
                $temp = explode('.', $module, 2);
                if(array_key_exists(1, $temp)){
                    $module = $temp[0];
                    $submodule = $temp[1];
                } else {
                    if(array_key_exists(2, $explode)){
                        $submodule = strtolower(File::basename($explode[2], $object->config('extension.tpl')));
                        $temp = explode('.', $submodule, 2);
                        if(array_key_exists(1, $temp)){
                            $submodule = $temp[0];
                            $command = $temp[1];
                        }
                    }
                }
            }

            if(array_key_exists(3, $explode)){
                $command = strtolower(File::basename($explode[3], $object->config('extension.tpl')));
                $temp = explode('.', $command, 2);
                if(array_key_exists(1, $temp)){
                    $command = $temp[0];
                    $subcommand = $temp[1];
                }
            }
            if(array_key_exists(4, $explode) && $subcommand === false){
                $subcommand = strtolower(File::basename($explode[4], $object->config('extension.tpl')));
                $temp = explode('.', $subcommand, 2);
                if(array_key_exists(1, $temp)){
                    $subcommand = $temp[0];
                    $action = $temp[1];
                }
            }
            if(array_key_exists(5, $explode) && $action === false){
                $action = strtolower(File::basename($explode[5], $object->config('extension.tpl')));
                $temp = explode('.', $subcommand, 2);
                if(array_key_exists(1, $temp)){
                    $action = $temp[0];
                    $subaction = $temp[1];
                }
            }
            if(array_key_exists(6, $explode) && $subaction === false){
                $subaction = strtolower(File::basename($explode[6], $object->config('extension.tpl')));
            }
            if(
                !in_array(
                    $category,
                    $scan['category'],
                    true
                )
            ){
                $scan['category'][] = $category;
            }
            if(
                $subcategory !== false &&
                !in_array(
                    $subcategory,
                    $scan['subcategory'],
                    true
                )
            ){
                $scan['subcategory'][] = $subcategory;
            }
            if(
                !in_array(
                    $module,
                    $scan['module'],
                    true
                )
            ){
                $scan['module'][] = $module;
            }
            if(
                $submodule &&
                !in_array(
                    $submodule,
                    $scan['submodule'],
                    true
                )
            ){
                $scan['submodule'][] = $submodule;
            }
            if(
                $command  &&
                !in_array(
                    $command,
                    $scan['command'],
                    true
                )
            ){
                $scan['command'][] = $command;
            }
            if(
                $subcommand &&
                !in_array(
                    $subcommand,
                    $scan['subcommand'],
                    true
                )
            ){
                $scan['subcommand'][] = $subcommand;
            }
            if(
                $action &&
                !in_array(
                    $action,
                    $scan['action'],
                    true
                )
            ){
                $scan['action'][] = $subcommand;
            }
            if(
                $subaction &&
                !in_array(
                    $subaction,
                    $scan['subaction'],
                    true
                )
            ){
                $scan['subaction'][] = $subcommand;
            }
        }
        return $scan;
    }
}