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

use R3m\Io\Node\Model\Unique;
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
                    foreach($explode as $explode_nr => $explode_value){
                        $explode[$explode_nr] = trim($explode_value);
                    }
                    ddd($record);
                    $value[$nr] = $object->request('node.' . $record);
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
                $options['filter'][$explode[0]] = $value[$nr];
                $options['sort'][$explode[0]] = 'ASC';
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
    /*
    if($url === false){
        throw new Exception('Url not set for Is.Unique');
    }
    if(File::exist($url) === false){
        throw new Exception('BinarySearch tree not found for Is.Unique (' . $url .')');
    }
    */
    $unique = $object->data('Is.Unique');
    if (empty($unique)) {
        $parse = new Parse($object);
        $data = new Storage();
        $unique = new Unique($parse, $data);
        $object->data('Is.Unique', $unique);
    }
    d($options);
    d($class);
    $record = $unique->record($class, $unique->role_system(), $options);
    if (empty($record)) {
        return true;
    }
    return false;
}
