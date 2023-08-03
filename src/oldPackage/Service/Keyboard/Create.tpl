{{R3M}}
{{$class = 'System.Keyboard'}}
{{$options = options()}}
{{if(
$options.options.command &&
$options.options.domain &&
$options.options.scope &&
$options.function
)}}
{{else}}
Create Keyboard:
{{$options.options.command = terminal.readline('Command: ')}}
{{$options.options.domain = terminal.readline('Domain: ')}}
{{$options.options.scope = terminal.readline('Scope: ')}}
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
'options' => $options.options,
'function' => $options.function,
'#class' => $class,
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

