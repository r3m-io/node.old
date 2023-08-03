{{R3M}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{$response = R3m.Io.Node:Data:list(
'App.Host.link',
R3m.Io.Node:Role:role.system(),
[
'sort' => [
'host' => 'ASC',
'link' => 'ASC',
],
'limit' => (int) $options.limit,
'page' => (int) $options.page,
'ramdisk' => true,
])}}
{{if($options.format === 'json')}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{else}}
List Host-Links:
{{if(is.array($response.list))}}
{{for.each($response.list as $nr => $host_link)}}
{{$selector = $nr + 1}}
[{{$selector}}] {{$host_link)}} {{$host|lowercase}} {{$host.link}}
{{/for.each}}
{{else}}
{{dd($response)}}
{{/if}}
{{/if}}

