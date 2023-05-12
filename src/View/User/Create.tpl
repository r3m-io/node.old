Create User:

{{$email = terminal.readline('Email: ')}}
{{$is_password = false}}
{{while($is_password === false)}}
{{$password = terminal.readline('Password: ', 'input-hidden')}}

{{$password_confirmation = terminal.readline('Password Confirmation: ', 'input-hidden')}}

{{if($password === $password_confirmation)}}
{{$is_password = true}}
{{break()}}
{{else}}
Passwords do not match!
{{/if}}
{{/while}}
{{$response = R3m.Io.Node:Data:list(
'Role',
[
'order' => [
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
[
'email' => $email,
'password' => password.hash($password, 13),
'role' => $roles
])}}

{{$response|json.encode:'JSON_PRETTY_PRINT'}}

