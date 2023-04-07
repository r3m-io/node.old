{{R3M}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{if($options.format === 'json')}}
{{else}}
Update User:

{{/if}}
{{$response = R3m.Io.Node:Data:list('User', [
'order' => [
'email' => 'ASC'
],
'limit' => (int) $options.limit,
'page' => (int) $options.page,
])}}
{{if(is.array($response.list))}}
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
{{$users = terminal.readline('User: ')}}
{{$users = preg_replace('/\s+/', ' ', $users)}}
{{$users = string.replace(', ', ',', $users)}}
{{if(string.contains.case.insensitive($users, 'all'))}}
{{$users = $response.list}}
{{else}}
{{$users = explode(',', $users)}}
{{for.each($users as $nr => $selector)}}
{{$selector = (int) $selector}}
{{if(array.key.exist($selector - 1, $response.list))}}
{{$users[$nr] = $response.list[$selector - 1]}}
{{/if}}
{{/for.each}}
{{/if}}
{{if(is.array($users))}}
{{for.each($users as $nr => $user)}}
{{$patch.uuid = $user.uuid}}
{{$patch = R3m.Io.Node:Data:read('User', [
'uuid' => $patch.uuid
])}}
{{dd($patch)}}
{{if($options.email)}}
{{$patch.email = $options.email}}
{{/if}}
{{if(
$options.password &&
$options.password_repeat &&
$options.password === $options.password_repeat
)}}
{{$patch.password = password.hash($options.password, 13)}}
{{/if}}
{{if($options.role)}}
{{$roles = $options.role}}
{{$roles = preg_replace('/\s+/', ' ', $roles)}}
{{$roles = string.replace(', ', ',', $roles)}}
{{if(string.contains.case.insensitive($roles, 'all'))}}
{{$roles = $response.list}}
{{else}}
{{$roles = explode(',', $roles)}}
{{for.each($roles as $nr => $selector)}}
{{$selector = (int) $selector}}
{{if(array.key.exist($selector - 1, $response.list))}}
{{$roles[$nr] = $response.list[$selector - 1]}}
{{/if}}
{{/for.each}}
{{/if}}
{{if(is.array($roles))}}
{{$list = R3m.Io.Node:Data:list_attribute($roles, ['uuid', 'name', 'rank'])}}
{{foreach($list as $nr => $role)}}
{{$patch.Role[] = $role}}
{{/foreach}}
{{/if}}
{{/if}}
{{$response = R3m.Io.Node:Data:patch('Role', $patch)}}
{{/for.each}}
{{/if}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

