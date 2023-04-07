{{R3M}}
{{$options = options()}}
{{if($options.format === 'json')}}
{{else}}
List Roles:

{{/if}}
{{$list = R3m.Io.Node:Data:list('Role', [
    'order' => [
    'rank' => 'ASC',
    'name' => 'ASC'
    ],
    'limit' => 255,
    'page' => 1,
])}}
{{$list|json.encode:'JSON_PRETTY_PRINT'}}

