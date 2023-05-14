{{R3M}}
{{$options = options()}}
{{if($options.format === 'json')}}
{{else}}
Read {{R3m.Io.Node:Data:module($r3m.io.parse.view.url)}}:

{{/if}}
{{$class = R3m.Io.Node:Data:module($r3m.io.parse.view.url)}}
{{if(is.empty($options.uuid))}}
You can use list to get the uuid.
{{$options.uuid = terminal.readline('Uuid: ')}}
{{/if}}
{{$response = R3m.Io.Node:Data:read(
$class,
R3m.io.Node:Role:role_system(),
[
'uuid' => $options.uuid
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

