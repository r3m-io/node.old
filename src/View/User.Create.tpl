Create User:

{{$email = terminal.readline('Email: ')}}
{{$password = terminal.readline('Password: ', 'input-hidden')}}

{{$password_confirmation = terminal.readline('Password Confirmation: ', 'input-hidden')}}

{{$response = R3m.Io.Node:Data:list('Role', [
'order' => [
'name' => 'ASC'
],
'limit' => 255,
'page' => 1,
])}}
Roles:
Use ',' to separate roles
{{if($response.list)}}
{{for.each($response.list as $nr => $role)}}
{{$selector = $nr + 1}}
[{{$selector}}] {{$role.name}}

{{/for.each}}
{{/if}}

{{$roles = terminal.readline('Choose Role(s): ')}}
{{$roles = preg_replace('/\s+/', ' ', $roles)}}
{{$roles = string.replace(', ', ',', $roles)}}
{{$roles = string.split(',', $roles)}}
{{for.each($roles as $nr => $selector)}}
{{$roles[$nr] = $response.list[$selector - 1]}}
{{/for.each}}
{{dd($roles)}}