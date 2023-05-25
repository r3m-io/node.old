{{R3M}}
Create Role:

{{$name = terminal.readline('Role: ')}}
{{$rank = (int) terminal.readline('Rank: ')}}
{{$response = R3m.Io.Node:Data:create(
'Role',
R3m.Io.Node:Role:role_system(),
[
'name' => $name,
'rank' => $rank,
])}}

{{$response|json.encode:'JSON_PRETTY_PRINT'}}

