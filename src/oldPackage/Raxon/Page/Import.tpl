{{R3M}}
{{$options = options()}}
{{if($options.confirmation !== 'y')}}
Package: R3m-io/Node
Module: RaXon
Submodule: Page
command: Import
{{/if}}
{{$class = 'RaXon.Io.Page'}}
{{$options.url = config('project.dir.data') +
'RaXon' +
'/' +
'Io' +
'/' +
'Pages.xml'
}}
{{if(is.empty($options.offset))}}
{{$options.offset = 0}}
{{/if}}
{{if(is.empty($options.limit))}}
{{$options.limit = 16777216}}
{{/if}}
{{if(is.empty($options.range))}}
{{$options.range = "00000000-00FFFFFF"}}
{{/if}}
{{$options.compression = [
'algorithm' => 'gz',
'level' => 9
]}}
{{$response = RaXon:Io:page.import(
$class,
R3m.Io.Node:Role:role.system(),
[
'url' => $options.url,
'offset' => $options.offset,
'limit' => $options.limit,
'compression' => $options.compression,
'range' => $options.range
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

