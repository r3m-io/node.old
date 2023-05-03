{{R3M}}
{{$options = options()}}
{{if($options.format === 'json')}}
{{else}}
Read Init:

{{/if}}
{{dd('{$this}')}}
{{$class = 'Init'}}
{{$read.options = (clone) $options}}
{{if(is.empty($options.uuid))}}
You can use list to get the uuid.
{{$options.uuid = terminal.readline('Uuid: ')}}
{{/if}}
{{$response = R3m.Io.Node:Data:read($class, ['uuid' => $options.uuid])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

