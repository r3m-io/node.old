{{R3M}}
{{$options = options()}}
{{if($options.format === 'json')}}
{{else}}
Read Node:

{{/if}}
{{if(is.empty($options.class))}}
{{$options.class = terminal.readline('Class: ')}}
{{/if}}
{{$read.options = (clone) $options}}
{{unset($read.options.class)}}
{{unset($read.options.format)}}
{{if(!is.empty($read.options))}}
{{$response = R3m.Io.Node:Data:read($options.class|uppercase.first, $read.options)}}
{{else}}
{{if(is.empty($options.uuid))}}
    {{$options.uuid = terminal.readline('Uuid: ')}}
    {{$response = R3m.Io.Node:Data:read($options.class|uppercase.first, ['uuid' => $options.uuid])}}
{{/if}}
{{/if}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

