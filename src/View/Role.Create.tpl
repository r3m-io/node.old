{{R3M}}
Create Role:

{{$name = terminal.readline('Role: ')}}
{{$rank = (int) terminal.readline('Rank: ')}}
{{$role = R3m.Io.Node:Data:create('Role', [
    'name' => $name,
    'rank' => $rank,
])}}

{{$role|json.encode:'JSON_PRETTY_PRINT'}}

