<?php
namespace R3m\Io\Node\Trait;

Trait Filter {

    /**
     * @throws \Exception
     */
    private function filter($record=[], $filter=[], $options=[]){

        $list = [];
        $list[] = $record;
        d($record);
        d($filter);
        $list = \R3m\Io\Module\Filter::list($list)->where($filter);
        if(!empty($list)){
            return $record;
        }
        return false;
    }
}