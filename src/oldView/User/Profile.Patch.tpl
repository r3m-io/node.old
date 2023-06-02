Create User.Profile:
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
{{if(!is.empty($user.profile))}}
{{$birthday = terminal.readline('Birthday (YYYY-MM-DD): ')}}
{{$response = R3m.Io.Node:Data:patch(
'User.Profile',
R3m.Io.Node:Role:role_system(),
[
'uuid' => $user.profile.uuid,
'birthday' => $birthday
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{else}}
User already has no profile...
Use {{binary()}} r3m-io/node user profile create
{{$user|json.encode:'JSON_PRETTY_PRINT'}}
{{/if}}

