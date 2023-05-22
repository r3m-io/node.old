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
{{$user = null}}
{{while($user === null)}}
{{$email = terminal.readline('Email: ')}}
{{$response = R3m.Io.Node:Data:record(
'User',
R3m.Io.Node:Role:role_system(),
[
'sort' => [
'email' => 'ASC',
],
'where' => [
[
'attribute' => 'email',
'value' => $email,
'operator' => 'partial'
]
]
])}}
{{if($response)}}
{{break()}}
{{else}}
Cannot find user...
{{/if}}
{{/while}}
{{$user = $response.node}}
{{$patch = $user}}
{{if($options.email)}}
{{$patch.email = $options.email}}
{{/if}}
{{if(
$options.password &&
$options['password-repeat'] &&
$options.password === $options['password-repeat']
)}}
{{$patch.password = password.hash($options.password, 13)}}
{{/if}}
/*
{{if($options.role)}}
{{if(!$options.role_page)}}
{{$options.role_page = 1}}
{{/if}}
{{if(!$options.role_limit)}}
{{$options.role_limit = 255}}
{{/if}}
{{/if}}
{{$response = R3m.Io.Node:Data:list(
'Role',
[
'order' => [
'rank' => 'ASC',
'name' => 'ASC'
],
'limit' => (int) $options.role_limit,
'page' => (int) $options.role_page,
])}}
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
{{for.each($list as $patch_nr => $patch_role)}}
{{for.each($patch.role as $nr => $role)}}
{{if($role.uuid === $patch_role.uuid)}}
{{$patch.role[$nr] = $patch_role}}
{{/if}}
{{/for.each}}
{{/for.each}}
{{$patch.role = data.sort($patch.role, [
'rank' => 'ASC',
'name' => 'ASC'
], true)}}
{{/if}}
*/
{{dd($patch)}}
{{$response = R3m.Io.Node:Data:patch('User', $patch)}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{/for.each}}
{{/if}}

