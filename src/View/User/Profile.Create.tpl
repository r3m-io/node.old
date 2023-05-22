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
{{if(is.empty($user.profile))}}
{{$birthday = terminal.readline('Birthday (YYYY-MM-DD): ')}}
{{$response = R3m.Io.Node:Data:create(
'User.Profile',
R3m.Io.Node:Role:role_system(),
[
'birthday' => $birthday,
'user' => $user.uuid
])}}
{{$user = R3m.Io.Node:Data:patch(
'User',
R3m.Io.Node:Role:role_system(),
[
'uuid' => $user.uuid,
'profile' => $response.node.uuid
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
{{else}}
User already has a profile ({{$user.profile.uuid}})...
{{$user|json.encode:'JSON_PRETTY_PRINT'}}
{{/if}}

