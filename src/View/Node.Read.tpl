{{R3M}}
{{$options = options()}}
{{if($options.format === 'json')}}
{{else}}
Read Node:

{{/if}}
{{if(is.empty($options.class))}}
{{$options.class = terminal.readline('Class: ')}}
{{/if}}
{{$class = $options.class|uppercase.first}}
{{$read.options = (clone) $options}}
{{unset('$read.options.class')}}
{{unset('$read.options.format')}}
{{if(!is.empty($read.options))}}

{{$response = R3m.Io.Node:Data:read($class, $read.options)}}
{{else}}
{{if(is.empty($options.uuid))}}
    {{$options.uuid = terminal.readline('Uuid: ')}}
    {{$response = R3m.Io.Node:Data:read($class, ['uuid' => $options.uuid])}}
{{/if}}
{{/if}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

