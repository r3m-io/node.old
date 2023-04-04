{{R3M}}
/*
{{R3m.Io.Node:Setup:install()}}
*/

{{$event.action = 'cli.autoload.run'}}
{{$event.options.command = []}}
{{$event.options.controller = []}}
{{$event.options.priority = 10}}

{{R3m.Io.Node:Data:create('Event', [
'action' => 'cli.autoload.run',
'options.command' => [],
'options.controller' => [],
'options.priority' => 10
])}}

Utilising the Node namespace:

{{literal}}
{{Node.Data.create($class, [
'attribute': 'string'
])}}
{{Node.Data.read($class, [
'uuid': 'string'
])}}
{{Node.Data.update($class, [
'uuid': 'string'
])}}
{{Node.Data.delete($class, [
'uuid': 'string'
])}}
{{Node.Data.list($class, [
'order' => [
    'id' => 'DESC'
],
'filter' => [
    'property[partial]' => ...
],
'limit' => 20,
'page' => 1
])}}
{{Node.Data.page($class, [
'order' => [
    'id' => 'DESC'
],
'filter' => [
    'property[partial]' => ...
],
'limit' => 20,
'page' => 1
])}}
{{Node.Data.import($class, [


])}}
{{Node.Data.export($class, [


])}}
{{Node.Data.backup($class, [


])}}
{{Node.Data.restore($class, [


])}}
{{/literal}}
