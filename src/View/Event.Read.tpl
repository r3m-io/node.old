{{R3M}}
{{if($options.format === 'json')}}
{{else}}
Read Event:

{{/if}}
{{$options = options()}}
{{if(is.empty($options.uuid))}}
    {{$options.uuid = terminal.readline('Uuid: ')}}
{{/if}}
{{$response = R3m.Io.Node:Data:read('Event', ['uuid' => $options.uuid])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

