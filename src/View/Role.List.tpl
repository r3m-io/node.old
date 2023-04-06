{{R3M}}
List Roles:

{{$list = R3m.Io.Node:Data:list('Role', [
    'order' => [
    'name' => 'ASC'
    ],
    'limit' => 20,
    'page' => 1,
])}}

{{$list|json.encode:'JSON_PRETTY_PRINT'}}

