{{R3M}}
Create Init:
{{$name = terminal.readline('Name: ')}}
{{$controller = terminal.readline('Controller: ')}}
{{$priority = (int) terminal.readline('Priority (10): ')}}
{{if(is.empty($priority))}}
{{$priority = 10}}
{{/if}}
{{$response = R3m.Io.Node:Data:create('Init', [
'name' => $name,
'controller' => $controller,
'priority' => $priority
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

