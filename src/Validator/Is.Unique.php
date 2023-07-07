<?php

/**
 * @author          Remco van der Velde
 * @since           2020-09-18
 * @copyright       Remco van der Velde
 * @license         MIT
 * @version         1.0
 * @changeLog
 *     -            all
 */


use R3m\Io\App;

use R3m\Io\Module\Dir;
use R3m\Io\Module\File;
use R3m\Io\Node\Model\Node;
use R3m\Io\Module\Data as Storage;
use R3m\Io\Module\Parse;

/**
 * @throws Exception
 */
function validate_is_unique(App $object, $value='', $attribute='', $validate='')
{
    $url = false;
    $class = false;
    if (is_object($validate)) {
        if (property_exists($validate, 'url')) {
            $url = $validate->url;
        }
        if (property_exists($validate, 'class')) {
            $class = $validate->class;
        }
        if (property_exists($validate, 'attribute')) {
            $attribute = $validate->attribute;
            if (is_array($attribute)) {
                $value = [];
                foreach ($attribute as $nr => $record) {
                    $explode = explode(':', $record);
                    /*
                    foreach($explode as $explode_nr => $explode_value){
                        $explode[$explode_nr] = trim($explode_value);
                    }
                    */
                    $value[$nr] = $object->request('node.' . $explode[0]);
                }
            }
        }
    }
    if (
        is_array($attribute) &&
        is_array($value)
    ) {
        $options = [
            'filter' => [],
            'sort' => []
        ];
        foreach ($attribute as $nr => $record) {
            if (array_key_exists($nr, $value)) {
                $explode = explode(':', $record);
                foreach($explode as $explode_nr => $explode_value){
                    $explode[$explode_nr] = trim($explode_value);
                }
                if(array_key_exists(1, $explode)){
                    $options['filter'][$explode[1]] = $value[$nr];
                    $options['sort'][$explode[1]] = 'ASC';
                } else {
                    $options['filter'][$explode[0]] = $value[$nr];
                    $options['sort'][$explode[0]] = 'ASC';
                }
            }
        }
    } else {
        $options = [
            'filter' => [
                $attribute => $value
            ],
            'sort' => [
                $attribute => 'ASC'
            ]
        ];
    }
    if($url === false){
        throw new Exception('Url not set for Is.Unique');
    }
    if(File::exist($url) === false){
        if($object->config('project.log.node')){
            $object->logger($object->config('project.log.node'))->info('Is.Unique: ' . $url . ' doesn\'t exist (new object) ?');
        }
        return true;
    }
    if($class === false){
        throw new Exception('Class not set for Is.Unique');
    }
    $url_uuid = $object->config('project.dir.data') .
        'Node' .
        $object->config('ds') .
        'BinaryTree' .
        $object->config('ds') .
        $class .
        $object->config('ds') .
        'Asc' .
        $object->config('ds') .
        'Uuid' .
        $object->config('extension.btree');

    $url_connect_property = Dir::name($url) . File::basename($url) . '.connect';
    $unique = $object->data('Is.Unique');
    if (empty($unique)) {
        $unique = new Node($object);
        $object->data('Is.Unique', $unique);
    }
    $node_ramisk = $object->config('package.r3m-io/node.ramdisk');
    if(empty($node_ramisk)){
        $node_ramisk = [];
    }
    if(
        in_array(
            $class,
            $node_ramisk,
            true
        )
    ){
        $options['ramdisk'] = true;
    }
    $record = $unique->record($class, $unique->role_system(), $options);
    ddd($record);
    if (empty($record)) {
        return true;
    }
    return false;
}
