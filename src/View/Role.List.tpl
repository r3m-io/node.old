{{R3M}}
{{$options = options()}}
{{dd($options)}}
List Roles:

{{$list = R3m.Io.Node:Data:list('Role', [
    'order' => [
    'rank' => 'ASC',
    'name' => 'ASC'
    ],
    'limit' => 255,
    'page' => 1,
])}}
{{$list|json.encode:'JSON_PRETTY_PRINT'}}

