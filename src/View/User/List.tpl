{{R3M}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{$response = R3m.Io.Node:Data:list(
'User',
[
'order' => [
'email' => 'ASC',
],
'limit' => (int) $options.limit,
'page' => (int) $options.page,
])}}
{{if($options.format === 'json')}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{else}}
List Users:
{{for.each($response.list as $nr => $user)}}
{{$selector = $nr + 1}}
{{$user_role = []}}
{{if(is.array($user.Role))}}
{{for.each($user.Role as $role)}}
{{$user_role[] = $role.name}}
{{/for.each}}
{{/if}}
[{{$selector}}] {{$user.email}} ({{implode(', ', $user_role)}})
{{/for.each}}
{{/if}}

