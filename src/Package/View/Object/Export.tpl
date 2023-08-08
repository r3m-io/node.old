{{R3M}}
{{$options = options()}}
{{while(is.empty($options.class))}}
{{$options.class = terminal.readline('Class: ')}}
{{/while}}
{{if(is.empty($options.url))}}
{{$options.url = config('project.dir.mount') +
'Backup' +
'/' +
'Package' +
'/' +
'R3m.Io.Node' +
'/' +
$options.class +
'/' +
date('Y-m-d-H-i-s') +
'/' +
$class +
config('extension.json')}}
{{/if}}
{{if(!is.empty($options.compression))}}
{{$options.compression = [
'algorithm' => 'gz',
'level' => 9
]}}
{{else}}
{{$options.compression = false}}
{{/if}}
{{R3m.Io.Node:Data:export(
$options.class,
R3m.Io.Node:Role:role.system(),
[
'url' => $options.url,
'compression' => $options.compression
])}}
