Create User:

{{$email = terminal.readline('Email: ')}}
{{$password = terminal.readline('Password: ', 'input-hidden')}}
{{$password_confirmation = terminal.readline('Password Confirmation: ', 'input-hidden')}}
{{$list = R3m.Io.Node:Data:list('Role', [
'order' => [
'name' => 'ASC'
],
'limit' => 255,
'page' => 1,
])}}
{{dd($list)}}
Roles:
Use ',' to separate roles
{{for.each($list as $nr => $role)}}
[{{$nr + 1}}] {{$role.name}}
{{/for.each}}
{{$roles = terminal.readline('Choose Role(s): ')}}

{{dd($roles)}}