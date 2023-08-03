{{R3M}}
{{$class = 'Keyboard'}}
{{$options = options()}}
{{if(
$options.command &&
$options.domain &&
$options.function
)}}
{{else}}
Create Keyboard:
{{$options.command = terminal.readline('Command: ')}}
{{$options.domain = terminal.readline('Domain: ')}}
{{$options.function = []}}
{{$function = terminal.readline('Function: ')}}
{{if(!is.empty($function))}}
{{$options.function[] = $function}}
{{/if}}
{{while(!is.empty($function))}}
{{$function = terminal.readline('Function: ')}}
{{if(!is.empty($function))}}
{{$options.function[] = $function}}
{{/if}}
{{/while}}
{{/if}}
{{$response = R3m.Io.Node:Data:create(
$class,
R3m.Io.Node:Role:role_system(),
[
'command' => $options.command,
'domain' => $options.domain,
'function' => $options.function,
'#class' => $class,
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

