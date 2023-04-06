{{R3M}}
/*
{{R3m.Io.Node:Setup:install()}}
*/

{{$event.action = 'cli.autoload.run'}}
{{$event.options.command = []}}
{{$event.options.controller = []}}
{{$event.options.priority = 10}}

/*
{{$test = [
'action' => 'cli.autoload.run',
'options' => [
'command' => [],
'controller' => [],
'priority' => 10
]]}}


{{R3m.Io.Node:Data:create('Event', $test, true)}}
*/

/*
{{R3m.Io.Node:Data:create('Event', [
'action' => 'cli.autoload.run',
'options' => [
'command' => [],
'controller' => [],
'priority' => 10
]], true)}}
*/

{{R3m.Io.Node:Setup:install()}}

Utilising the Node namespace:

{{literal}}
{{R3m.Io.Node:Data:create($class, [
'attribute': 'string'
])}}
{{R3m.Io.Node:Data:read($class, [
'uuid': 'string'
])}}
{{R3m.Io.Node:Data:update($class, [
'uuid': 'string'
])}}
{{R3m.Io.Node:Data:delete($class, [
'uuid': 'string'
])}}
{{R3m.Io.Node:Data:list($class, [
'order' => [
    'id' => 'DESC'
],
'filter' => [
    'property[partial]' => ...
],
'limit' => 20,
'page' => 1
])}}
{{R3m.Io.Node:Data:page($class, [
'order' => [
    'id' => 'DESC'
],
'filter' => [
    'property[partial]' => ...
],
'limit' => 20
])}}
{{R3m.Io.Node:Data:import($class, [


])}}
{{R3m.Io.Node:Data:export($class, [


])}}
{{R3m.Io.Node:Data:backup($class, [


])}}
{{R3m.Io.Node:Data:restore($class, [


])}}
{{/literal}}
