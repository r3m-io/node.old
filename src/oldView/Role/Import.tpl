{{R3M}}
Import Role:
{{$class = 'Role'}}
{{$options = options()}}
{{if($options.url) && file.exist($options.url)}}
{{$url = $options.url}}
{{else}}
{{while(true)}}
{{$url = terminal.readline('url: ')}}
{{if(file.exist($url))}}
{{break()}}
{{else}}
File not found. ({{$url}})
{{/if}}
{{/while}}
{{/if}}

{{$response = R3m.Io.Node:Data:import(
$class,
[
'url' => $url,
])}}

{{$response|json.encode:'JSON_PRETTY_PRINT'}}

