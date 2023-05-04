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
    if(is_object($validate)){
        if(property_exists($validate, 'url')){
            $url = $validate->url;
        }
    }
    if($url === false){
        throw new Exception('Url not set for Is.Unique');
    }
    if(File::exist($url) === false){
        throw new Exception('BinarySearch tree not found for Is.Unique (' . $url .')');
    }
    d($url);

    d($value);
    ddd($validate);

}
