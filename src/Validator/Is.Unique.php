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
use R3m\Io\Module\File;

/**
 * @throws Exception
 */
function validate_is_unique(App $object, $value='', $attribute='', $validate='')
{
    $url = false;
    $class = false;
    if(is_object($validate)){
        if(property_exists($validate, 'url')){
            $url = $validate->url;
        }
        if(property_exists($validate, 'class')){
            $class = $validate->class;
        }
        if(property_exists($validate, 'attribute')){
            $attribute = $validate->attribute;
            if(is_array($attribute)){
                $value = [];
                foreach($attribute as $nr => $record){
                    $value[$nr] = $object->request('node.' . $record);
                }
            }
        }
        /*
        if(property_exists($validate, 'value')){
            $value = $validate->value;
            if(is_array($value)){
                foreach($value as $nr => $record){
                    $value[$nr] = $object->request('node.' . $record);
                }
            }
        }
        */
    }
    if(
        is_array($attribute) &&
        is_array($value)
    ){
        $options = [
            'filter' => [],
            'sort' => []
        ];
        d($attribute);
        d($value);
        d($object->request('node'));

        foreach($attribute as $nr => $record){
            if(array_key_exists($nr, $value)){
                $options['filter'][$record] = $value[$nr];
                $options['sort'][$record] = 'ASC';
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

    $unique = new Unique();
    $record = $unique->record($class, $options);
    d($url);
    d($record);
    d($value);
    ddd($validate);

}

class Unique {
    use R3m\Io\Node\Trait\Data;

}
