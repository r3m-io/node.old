{{R3M}}
{{$options = options()}}
{{if($options.format === 'json')}}
{{else}}
Read Event:

{{/if}}
{{if(is.empty($options.uuid))}}
You can use list to get the uuid.
{{$options.uuid = terminal.readline('Uuid: ')}}
{{/if}}
{{$response = R3m.Io.Node:Data:read(
'App.Event',
R3m.Io.Node:Role:role_system(),
[
'uuid' => $options.uuid
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

