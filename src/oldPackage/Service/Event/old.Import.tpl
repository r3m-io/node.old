{{R3M}}
{{$class = 'App.Event'}}
{{R3m.Io.Node:Data:import(
$class,
R3m.Io.Node:Role:role.system(),
[
'url' => config('project.dir.mount') +
'Backup' +
'/' +
'Package' +
'/' +
'R3m.Io.Node' .
'/' +
$class +
'/'
])}}

