Create UserProfile:
{{$is_user = false}}
{{while($is_user === false)}}
{{$email = terminal.readline('Email: ')}}
{{$response = R3m.Io.Node:Data:record(
'User',
R3m.Io.Node:Role:role_system(),
[
'sort' => [
'email' => 'ASC',
],
'filter' => [
'email' => [
'value' => $email,
'operator' => 'partial'
]
]
])}}
{{dd($response)}}
{{/while}}
/*
{{$response = R3m.Io.Node:Data:list(
'Role',
R3m.Io.Node:Role:role_system(),
[
'sort' => [
'rank' => 'ASC',
'name' => 'ASC'
],
'limit' => 255,
'page' => 1,
])}}
Roles:
Use ',' to separate roles, 'All' for all roles.
{{if($response.list)}}
{{for.each($response.list as $nr => $role)}}
{{$selector = $nr + 1}}
[{{$selector}}] {{$role.name}}

{{/for.each}}
{{/if}}

{{$roles = terminal.readline('Choose Role(s): ')}}
{{$roles = preg_replace('/\s+/', ' ', $roles)}}
{{$roles = string.replace(', ', ',', $roles)}}
{{if(string.contains.case.insensitive($roles, 'all'))}}
{{$roles = $response.list}}
{{else}}
{{$roles = explode(',', $roles)}}
{{for.each($roles as $nr => $selector)}}
{{if(array.key.exist($selector - 1, $response.list))}}
{{$roles[$nr] = $response.list[$selector - 1]}}
{{/if}}
{{/for.each}}
{{/if}}
{{for.each($roles as $nr => $role)}}
{{$roles[$nr] = $role.uuid}}
{{/for.each}}
{{$response = R3m.Io.Node:Data:create(
'User',
R3m.Io.Node:Role:role_system(),
[
'email' => $email,
'password' => password.hash($password, 13),
'password_confirmation' => $password_confirmation,
'role' => $roles
])}}
*/
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

