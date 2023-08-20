<?php

namespace R3m\Io\Node\Trait;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Module\Parse;
use R3m\Io\Module\Parse\Token;

Trait Tree {

    private function tree_max_depth($tree=[]){
        $depth = 0;
        if(!is_array($tree)){
            return $depth;
        }
        foreach($tree as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('depth', $record)){
                if($record['depth'] > $depth){
                    $depth = $record['depth'];
                }
            }
        }
        return $depth;
    }

    private function tree_get_set(&$tree, $depth=0): array
    {
        $is_collect = false;
        $set = [];
        foreach($tree as $nr => $record){
            if(
                is_array($record) &&
                array_key_exists('depth', $record) &&
                $record['depth'] === $depth
            ){
                $is_collect = true;
            }
            if($is_collect){
                if(
                    is_array($record) &&
                    array_key_exists('depth', $record) &&
                    $record['depth'] <> $depth){
                    $is_collect = false;
                    break;
                }
                $set[] = $record;
            }
        }
        return $set;
    }

    private function tree_set_replace($tree=[], $set=[], $depth=0){
        $is_collect = false;
        foreach($tree as $nr => $record){
            if(
                $is_collect === false &&
                is_array($record) &&
                array_key_exists('depth', $record) &&
                $record['depth'] === $depth
            ){
                $is_collect = $nr;
                continue;
            }
            if($is_collect){
                if(
                    is_array($record) &&
                    array_key_exists('depth', $record) &&
                    $record['depth'] <> $depth){
                    $tree[$is_collect] = [];
                    $tree[$is_collect]['set'] = $set;
                    $is_collect = false;
                    break;
                }
                unset($tree[$nr]);
            }
        }
        return $tree;
    }

    /**
     * @throws ObjectException
     * @throws FileWriteException
     */
    private function tree_record_attribute($record=[])
    {
        $attribute = '';
        if(!array_key_exists('collection', $record)){
            switch($record['type']){
                case Token::TYPE_QUOTE_DOUBLE_STRING:
                    if(strpos($record['value'], '{') === false){
                        return substr($record['value'], 1, -1);
                    }
                    //parse string...
                    $object = $this->object();
                    $storage = $this->storage();
                    $parse = new Parse($object);
                    ddd($record);
                    $result = $parse->compile($record['value'], $storage, $object);
                    if(
                        is_string($result) &&
                        substr($result, 0, 1) === '"' &&
                        substr($result, -1) === '"'
                    ){
                        $result = substr($result, 1, -1);
                    }
                    return $result;
                case Token::TYPE_QUOTE_SINGLE_STRING:
                    return substr($record['value'], 1, -1);
            }
            return array_key_exists('execute', $record) ? $record['execute'] : $record['value'];
        }
        if(!is_array($record['collection'])){
            switch($record['type']){
                case Token::TYPE_QUOTE_DOUBLE_STRING:
                case Token::TYPE_QUOTE_SINGLE_STRING:
                    return substr($record['value'], 1, -1);

            }
            return array_key_exists('execute', $record) ? $record['execute'] : $record['value'];
        }
        foreach($record['collection'] as $nr => $item){
            $attribute .= array_key_exists('execute', $item) ? $item['execute'] : $item['value'];
        }
        return $attribute;
    }

}
