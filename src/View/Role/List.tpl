{{R3M}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{$response = R3m.Io.Node:Data:list(
'Role',
[
'sort' => [
'rank' => 'ASC',
'name' => 'ASC'
],
'limit' => (int) $options.limit,
'page' => (int) $options.page,
])}}
{{if($options.format === 'json')}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{else}}
List Roles:
{{for.each($response.list as $nr => $role)}}
{{$selector = $nr + 1}}
[{{$selector}}] {{$role.name}} ({{$role.rank}})
{{/for.each}}
{{/if}}

