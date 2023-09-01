<?php

namespace R3m\Io\Node\Trait\Data;

use R3m\Io\Module\Core;
use R3m\Io\Module\Controller;

use R3m\Io\Node\Service\Security;

use Exception;

use R3m\Io\Exception\FileWriteException;
use R3m\Io\Exception\ObjectException;

Trait Truncate {

    /**
     * @throws ObjectException
     * @throws FileWriteException
     * @throws Exception
     */
    public function truncate($class, $role, $options=[]): array
    {
        if(!array_key_exists('function', $options)){
            $options['function'] = __FUNCTION__;
        }
        if(!Security::is_granted(
            $class,
            $role,
            Core::object($options, Core::OBJECT_ARRAY)
        )){
            return [];
        }
        $name = Controller::name($class);
        $object = $this->object();
        $meta_url = $object->config('project.dir.data') .
            'Node' .
            $object->config('ds') .
            'Meta' .
            $object->config('ds') .
            $name .
            $object->config('extension.json')
        ;
        $meta = $object->data_read($meta_url);
        $list_options = [
            'sort' => [
                'uuid' => 'asc'
            ],
            'limit' => $options['limit'] ?? 1000,
        ];
        $properties = [];
        $url_key = 'url.';
        if(!array_key_exists('sort', $list_options)){
            $debug = debug_backtrace(true);
            ddd($debug[0]['file'] . ' ' . $debug[0]['line']);
        }
        foreach($list_options['sort'] as $key => $order) {
            if(empty($properties)){
                $url_key .= 'asc.';
            } else {
                $url_key .= strtolower($order) . '.';
            }
            $properties[] = $key;
        }
        $url_key = substr($url_key, 0, -1);
        $sort_key = [
            'property' => $properties,
        ];
        $sort_key = sha1(Core::object($sort_key, Core::OBJECT_JSON));
        $count = $meta->get('Sort.' . $name . '.' . $sort_key . '.' . 'count');
//        $url_property = $meta->get('Sort.' . $name . '.' . $sort_key . '.' . $url_key);
        $page_max = ceil($count / $list_options['limit']);
        $result = [];
        for($page=1; $page <= $page_max; $page++) {
            $list_options['page'] = $page;
            $response = $this->list($name, $role, $list_options);
            $list = [];
            foreach ($response['list'] as $record) {
                if(
                    is_array($record) &&
                    array_key_exists('uuid', $record)
                ){
                    $list[] = $record['uuid'];
                }
                elseif(
                    is_object($record) &&
                    property_exists($record, 'uuid')
                ){
                    $list[] = $record->uuid;
                }
            }
            $delete_many = $this->delete_many($name, $role, [
                'uuid' => $list
            ]);
            foreach($delete_many as $uuid => $delete){
                $result[] = $uuid;
            }
        }
        $meta->delete('Filter');
        $meta->delete('Where');
        $meta->delete('Count');
        $meta->write($meta_url);
        return $result;
    }

}
