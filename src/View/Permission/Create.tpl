{{R3M}}
{{$class = 'Permission'}}
Create {{$class}}:
{{$name = string.trim(terminal.readline('Name: '))}}
{{while(true)}}
{{$role.name = string.trim(terminal.readline('Role: '))}}
{{$role.system = R3m.Io.Node:Role:role_system()}}
{{$role.selected = R3m.Io.Node:Data:record(
'Role',
$role.system,
[
'where' => [
[
'attribute' => 'name',
'value' => $role.name,
'operator' => 'partial'
]
],
'sort' => [
    'name' => 'ASC'
]
])}}
{{if(!is.empty($role.selected))}}
{{break()}}
{{/if}}
{{/while}}
{{$attributes = []}}
{{while(true)}}
{{$attribute = terminal.readline('Attribute: ')}}
{{if(is.empty($attribute))}}
{{break()}}
{{else}}
{{$attributes[] = $attribute|trim}}
{{/if}}
{{/while}}
{{$response = R3m.Io.Node:Data:create(
$class,
$role,
[
'name' => $name,
'attribute' => $attributes,
'role' => $role.selected.uuid
])}}
{{if(is.empty($role.selected.permission))}}
{{$role.selected.permission = []}}
{{/if}}
{{$permissions = $role.selected.permission}}
{{for.each($permissions as $nr => $permission)}}
{{if(!is.empty($permission.uuid))}}
{{$permissions[$nr] = $permission.uuid}}
{{/if}}
{{/for.each}}
{{$permissions[] = $response.node.uuid}}
{{$role.patched = R3m.Io.Node:Data:patch(
'Role',
$role.system,
[
'uuid' => $role.selected.uuid,
'permission' => $permissions
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

