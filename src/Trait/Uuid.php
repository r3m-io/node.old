<?php
namespace R3m\Io\Node\Trait;

Trait Uuid {

    public function is($string=''): bool
    {
        //format: %s%s-%s-%s-%s-%s%s%s
        $explode = explode('-', $string);
        $result = false;
        if(strlen($string) !== 36){
            return $result;
        }
        if(count($explode) !== 5){
            return $result;
        }
        if(strlen($explode[0]) !== 8){
            return $result;
        }
        if(strlen($explode[1]) !== 4){
            return $result;
        }
        if(strlen($explode[2]) !== 4){
            return $result;
        }
        if(strlen($explode[3]) !== 4){
            return $result;
        }
        if(strlen($explode[4]) !== 12){
            return $result;
        }
        return true;
    }

    public function compare($uuid='', $compare='', $operator='==='): bool
    {
        $uuid = explode('-', $uuid);
        $compare = explode('-', $compare);
        $result = [];
        foreach($uuid as $nr =>  $hex){
            $dec = hexdec($hex);
            $dec_compare = hexdec($compare[$nr]);
            switch($operator){
                case '===' :
                    if($dec === $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                    break;
                case '==' :
                    if($dec == $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                    break;
                case '>=' :
                    if($dec === $dec_compare){
                        $result[$nr] = true;
                        break;
                    }
                    if($dec > $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '<=' :
                    if($dec === $dec_compare){
                        $result[$nr] = true;
                        break;
                    }
                    if($dec < $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '>' :
                    if($dec > $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    }
                    elseif($dec === $dec_compare){
                        break;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '<' :
                    if($dec < $dec_compare){
                        $result[$nr] = true;
                        break 2;
                    }
                    elseif($dec === $dec_compare){
                        break;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                case '!==' :
                    if($dec !== $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                    break;
                case '!=' :
                    if($dec != $dec_compare){
                        $result[$nr] = true;
                    } else {
                        $result[$nr] = false;
                        break 2;
                    }
                    break;
            }
        }
        if(in_array(false, $result, true)){
            return false;
        }
        return true;
    }

}