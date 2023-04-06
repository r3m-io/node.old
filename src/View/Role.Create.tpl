{{R3M}}

Create Role:

{{$name = terminal.readline('Role:')}}
{{$role = R3m.Io.Node:Data:create('Role', [
    'name' => $name,
])}}

{{$role|json.encode:'JSON_PRETTY_PRINT'}}
