{{R3M}}
Create Event:

{{$commands = []}}
{{$controllers = []}}
{{$action = terminal.readline('Action: ')}}
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
{{$priority = (int) terminal.readline('Priority (10): ')}}
{{if(is.empty($priority))}}
{{$priority = 10}}
{{/if}}
{{$response = R3m.Io.Node:Data:create('Event', [
'action' => $action,
'options' => [
'command' => $commands,
'controller' => $controllers,
'priority' => $priority,
]
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

