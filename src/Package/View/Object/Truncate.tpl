{{R3M}}
{{$request = request()}}
{{$options = options()}}
{{if($options.confirmation !== 'y')}}
Package: {{$request.package}}
Module: {{$request.module|uppercase.first}}
Submodule: {{$request.submodule|uppercase.first}}
{{/if}}
{{while(is.empty($options.class))}}
{{$options.class = terminal.readline('Class: ')}}
{{/while}}
{{while($options.confirmation !== 'y'))}}
{{$options.confirmation = terminal.readline('Are you sure you want to truncate (Class:' + $options.class + ') (y/n): ')}}
{{if($options.confirmation === 'n')}}
{{exit()}}
{{/if}}
{{/while}}
{{R3m.Io.Node:Data:truncate(
$options.class,
R3m.Io.Node:Role:role.system(),
[
])}}
