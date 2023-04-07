{{R3M}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{if($options.format === 'json')}}
{{else}}
List Users:

{{/if}}
{{$response = R3m.Io.Node:Data:list('User', [
    'order' => [
    'email' => 'ASC',
    ],
    'limit' => (int) $options.limit,
    'page' => (int) $options.page,
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

