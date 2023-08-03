{{R3M}}
Update App Event:
Package: r3m-io/node
Module: App
Submodule: Event
command: update
{{$class = 'App.Event'}}
{{$options = options()}}
{{$response = R3m.Io.Node:Data:read(
$class,
R3m.Io.Node:Role:role_system(),
[
'uuid' => $options.uuid,
]
)}}
{{if(is.empty($response.node.uuid))}}
{{$class}} not found...
{{else}}
{{$commands = []}}
{{$controllers = []}}
Action: (will be overwritten)
{{$response.node.action}}

{{$action = terminal.readline('Action: ')}}
Commands: (will be overwritten)
{{for.each($response.node.options.command as $command)}}
{{$command}}

{{/for.each}}
{{$command = terminal.readline('Command: ')}}
{{if(!is.empty($command))}}
{{$commands[] = $command}}
{{/if}}
{{while(!is.empty($command))}}
{{$command = terminal.readline('Command: ')}}
{{if(!is.empty($command))}}
{{$commands[] = $command}}
{{/if}}
{{/while}}
Controllers: (will be overwritten)
{{for.each($response.node.options.controller as $controller)}}
{{$controller}}

{{/for.each}}
{{$controller = terminal.readline('Controller: ')}}
{{if(!is.empty($controller))}}
{{$controllers[] = $controller}}
{{/if}}
{{while(!is.empty($controller))}}
{{$controller = terminal.readline('Controller: ')}}
{{if(!is.empty($controller))}}
{{$controller[] = $controller}}
{{/if}}
{{/while}}
Priority: (will be overwritten)
{{$response.node.options.priority}}

{{$priority = (int) terminal.readline('Priority (10): ')}}
{{if(is.empty($priority))}}
{{$priority = 10}}
{{/if}}
{{d($class)}}
{{$response = R3m.Io.Node:Data:put(
$class,
R3m.Io.Node:Role:role_system(),
[
'uuid' => $options.uuid,
'action' => $action,
'options' => [
'command' => $commands,
'controller' => $controllers,
'priority' => $priority,
]
])}}
{{/if}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

