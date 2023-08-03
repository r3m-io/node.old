{{R3M}}
{{$class = 'Server.Event'}}
{{$options = options()}}
{{if(
$options.action &&
(
$options.options.command ||
$options.options.controller
) &&
$options.options.priority
)}}
{{$explode_commands = explode(',', data.extract('options.options.command'))}}
{{$commands = []}}
{{foreach($explode_commands as $nr => $command)}}
{{$command = string.trim($command)}}
{{if(!is.empty($command))}}
{{$commands[$nr] = $command}}
{{/if}}
{{/foreach}}
{{$options.command = $commands}}
{{$explode_controllers = explode(',', data.extract('options.options.controller'))}}
{{$controllers = []}}
{{foreach($explode_controllers as $nr => $controller)}}
{{$controller = string.trim($controller)}}
{{if(!is.empty($controller))}}
{{$controllers[$nr] = $controller}}
{{/if}}
{{/foreach}}
{{$options.controller = $controllers}}
{{$action = data.extract('options.action')}}
{{$options.priority = data.extract('options.options.priority')}}
{{else}}
Create Event:
{{$options.commands = []}}
{{$options.controllers = []}}
{{$action = terminal.readline('Action: ')}}
{{$command = terminal.readline('Command: ')}}
{{if(!is.empty($command))}}
{{$options.commands[] = $command}}
{{/if}}
{{while(!is.empty($command))}}
{{$command = terminal.readline('Command: ')}}
{{if(!is.empty($command))}}
{{$options.commands[] = $command}}
{{/if}}
{{/while}}
{{$controller = terminal.readline('Controller: ')}}
{{if(!is.empty($controller))}}
{{$options.controllers[] = $controller}}
{{/if}}
{{while(!is.empty($controller))}}
{{$controller = terminal.readline('Controller: ')}}
{{if(!is.empty($controller))}}
{{$options.controllers[] = $controller}}
{{/if}}
{{/while}}
{{$options.priority = (int) terminal.readline('Priority (10): ')}}
{{if(is.empty($options.priority))}}
{{$options.priority = 10}}
{{/if}}
{{/if}}
{{$response = R3m.Io.Node:Data:create(
$class,
R3m.Io.Node:Role:role_system(),
[
'action' => $action,
'options' => $options,
'#class' => $class,
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

