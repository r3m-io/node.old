{{R3M}}
{{$request = request()}}
{{$options = options()}}
Package: {{$request.package}}

Module: {{$request.module|uppercase.first}}

Submodule: {{$request.submodule|uppercase.first}}

{{while(is.empty($options.class))}}
{{$options.class = terminal.readline('Class: ')}}
{{/while}}
{{while(!file.exist($options.url))}}
{{if(
!is.empty($options.url) &&
!file.exist($options.url)
)}}
File not found: {{$options.url}}

{{/if}}
{{$options.url = terminal.readline('Url: ')}}
{{/while}}
{{$class = controller.name($options.class)}}
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
'offset' => $options.offset,
'limit' => $options.limit,
'compression' => $options.compression
])}}
Amount ({{$class}}) : {{$response.count}}

