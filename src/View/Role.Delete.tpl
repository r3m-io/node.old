{{R3M}}
Delete Role:
Use ',' to separate roles, 'All' for all roles.
{{$response = R3m.Io.Node:Data:list('Role', [
'order' => [
'name' => 'ASC'
],
'limit' => 255,
'page' => 1,
])}}
{{if(is.array($response.list))}}
{{for.each($response.list as $nr => $role)}}
{{$selector = $nr + 1}}
[{{$selector}}] {{$role.name}} ({{$role.rank}})
{{/for.each}}
{{/if}}

{{$roles = terminal.readline('Role: ')}}
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
{{$list = R3m.Io.Node:Data:list_attribute($roles, ['uuid', 'name'])}}
{{for.each($list as $role)}}
{{if(!is.empty($role.uuid))}}
{{$delete = R3m.Io.Node:Data:delete('Role', ['uuid' => $role.uuid])}}
{{if(
$delete &&
!is.empty($role.name)
)}}
{{$role.name}} deleted.
{{/if}}
{{/if}}
{{/for.each}}