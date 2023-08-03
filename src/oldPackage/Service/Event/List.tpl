{{R3M}}
{{$class = 'Server.Event'}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{$response = R3m.Io.Node:Data:list(
$class,
R3m.Io.Node:Role:role.system(),
[
'sort' => [
'action' => 'ASC',
'options.priority' => 'ASC'
],
'limit' => (int) $options.limit,
'page' => (int) $options.page
])}}
{{if($options.format === 'json')}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{else}}
List Events:
{{for.each($response.list as $nr => $event)}}
{{$selector = $nr + 1}}
[{{$selector}}] {{$event.uuid)}} {{$event.action}} ({{$event.options.priority)}})
{{/for.each}}
{{/if}}

