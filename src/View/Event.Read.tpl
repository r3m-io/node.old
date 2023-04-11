{{R3M}}
Read Event:
{{$options = options()}}
{{if(is.empty($options.uuid))}}
    {{$options.uuid = terminal.readline('Uuid: ')}}
{{/if}}
{{$response = R3m.Io.Node:Data:read('Event', ['uuid' => $options.uuid])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

