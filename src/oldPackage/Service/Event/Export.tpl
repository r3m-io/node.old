{{R3M}}
{{$class = 'App.Event'}}
{{R3m.Io.Node:Data:export(
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
'/' +
date('Y-m-d-H-i-s') +
'/' +
$class +
config('extension.json'),
'compression' => [
    'algorithm' => 'gz',
    'level' => 9
]
])}}

