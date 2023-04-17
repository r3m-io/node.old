{{R3M}}
Create Node File from directory:
{{$options = options()}}
{{if($options.directory)}}
    {{$directory = $options.directory}}
{{else}}
    {{$directory = terminal.readline('Directory: ')}}
    {{while(is.empty($directory))}}
    {{$directory = terminal.readline('Directory: ')}}
    {{if(!is.empty($directory))}}
        {{break()}}
    {{/if}}
    {{/while}}
{{/if}}
{{if($options.recursive)}}
    {{$recursive = $options.recursive}}
{{else}}
    {{$recursive = terminal.readline('recursive (y/n) : ')}}
    {{while(is.empty($recursive))}}
    {{$recursive = terminal.readline('recursive (y/n) : ')}}
    {{if(!is.empty($directory))}}
        {{break()}}
    {{/if}}
    {{/while}}
{{/if}}
{{if($recursive === 'y')}}
    {{$recursive = true}}
{{else}}
    {{$recursive = false}}
{{/if}}
{{$response = R3m.Io.Node:Data:file_create_many([
'directory' => $directory,
'recursive' => $recursive
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

