{{R3M}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{$response = R3m.Io.Node:Data:list(
'App.Host',
R3m.Io.Node:Role:role.system(),
[
'sort' => [
'domain' => 'ASC',
'name' => 'ASC',
],
'limit' => (int) $options.limit,
'page' => (int) $options.page,
'ramdisk' => true,
])}}
{{if($options.format === 'json')}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{else}}
List Hosts:
{{if(is.array($response.list))}}
{{for.each($response.list as $nr => $host)}}
{{$selector = $nr + 1}}
{{if(is.empty($host.subdomain))}}
[{{$selector}}] {{$host.uuid)}} {{$host.name|lowercase}} {{$host.domain}} {{$host.extension}}
{{else}}
[{{$selector}}] {{$host.uuid)}} {{$host.name|lowercase}} {{$host.subdomain}} {{$host.domain}} {{$host.extension}}
{{/if}}

{{/for.each}}
{{else}}
{{dd($response)}}
{{/if}}
{{/if}}

