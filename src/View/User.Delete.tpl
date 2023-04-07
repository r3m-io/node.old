{{R3M}}
Delete User:
Use ',' to separate users, 'All' for all users.
{{$response = R3m.Io.Node:Data:list('User', [
'order' => [
'email' => 'ASC'
],
'limit' => 255,
'page' => 1,
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
{{if(array.key.exist($selector - 1, $response.list))}}
{{$users[$nr] = $response.list[$selector - 1]}}
{{/if}}
{{/for.each}}
{{/if}}
{{$list = R3m.Io.Node:Data:list_attribute($users, ['uuid', 'email'])}}
{{for.each($list as $user)}}
{{if(!is.empty($user.uuid))}}
{{$delete = R3m.Io.Node:Data:delete('User', ['uuid' => $user.uuid])}}
{{if(
$delete &&
!is.empty($user.email)
)}}
{{$user.email}} deleted.
{{/if}}
{{/if}}
{{/for.each}}