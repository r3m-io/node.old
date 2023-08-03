{{R3M}}
{{$options = options()}}
{{if(is.empty($options.core))}}
{{$options.core = 1}}
{{/if}}
{{if($options.core === 1)}}
{{if($options.confirmation !== 'y')}}
Package: R3m-io/Node
Module: Object
Submodule: Import
{{/if}}
{{while(is.empty($options.class))}}
{{$options.class = terminal.readline('Class: ')}}
{{/while}}
{{while($options.confirmation !== 'y'))}}
{{$options.confirmation = terminal.readline('Are you sure you want to import (Class:' + $options.class + ') (y/n): ')}}
{{if($options.confirmation === 'n')}}
{{exit()}}
{{/if}}
{{/while}}
{{$class = controller.name($options.class)}}
{{if(is.empty($options.url))}}
{{$options.url = config('project.dir.mount') +
'Backup' +
'/' +
'Package' +
'/' +
'R3m.Io.Node' +
'/' +
$class +
'/'
}}
{{$is.url = false}}
{{else}}
{{$is.url = true}}
{{/if}}
{{if(is.empty($options.offset))}}
{{$options.offset = 0}}
{{/if}}
{{if(is.empty($options.limit))}}
{{$options.limit = '100%'}}
{{/if}}
{{if(is.empty($options.compression))}}
{{$options.compression = false}}
{{else}}
{{$options.compression = [
'algorithm' => 'gz',
'level' => 9
]}}
{{/if}}
{{$response = R3m.Io.Node:Data:import(
$class,
R3m.Io.Node:Role:role.system(),
[
'url' => $options.url,
'is_url' => $is.url,
'offset' => $options.offset,
'limit' => $options.limit,
'compression' => $options.compression
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}


{{else}}
{{$offset = 0}}
{{$options.offset = $offset + '%'}}
{{$limit = (100 / $options.core)}}
{{$options.limit = $limit + '%'}}
{{for($i=1; $i <= $options.core; $i++)}}
{{execute.background(binary() + ' r3m-io/node object import -class=' + $options.class + ' -url=' + $options.url + ' -confirmation=y -offset=' + $options.offset + ' -limit=' + $options.limit + ' -force=true -ramdisk=true -is_new=true')}}
{{$offset = $offset + $limit}}
{{$options.offset = $offset + '%'}}
{{/for}}
{{/if}}