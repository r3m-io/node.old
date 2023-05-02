{{R3M}}
{{$options = options()}}
{{if($options.format === 'json')}}
{{else}}
Read Event:

{{/if}}
{{if(!is.empty($options))}}
{{$response = R3m.Io.Node:Data:read('Event', $options)}}
{{else}}
{{if(is.empty($options.uuid))}}
    {{$options.uuid = terminal.readline('Uuid: ')}}
    {{$response = R3m.Io.Node:Data:read('Event', ['uuid' => $options.uuid])}}
{{/if}}
{{/if}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

