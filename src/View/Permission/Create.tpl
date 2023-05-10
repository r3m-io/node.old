{{R3M}}
{{$class = 'Permission'}}
Create {{$class}}:
{{$name = string.trim(terminal.readline('Name: '))}}
{{while(true)}}
{{$role_name = string.trim(terminal.readline('Role: '))}}
{{$role = R3m.Io.Node:Role:role_system()}}
{{dd($role)}}
{{$role = R3m.Io.Node:Data:record(
'Role',
$role,
[
'where' => [
[
'attribute' => 'name',
'value' => $role_name,
'operator' => 'partial'
]
],
'sort' => [
    'name' => 'ASC'
]
])}}
{{if(!is.empty($role))}}
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
'role' => $role.uuid
])}}
{{if(is.empty($role.permission))}}
{{$role.permission = []}}
{{/if}}
{{$permissions = $role.permission}}
{{for.each($permissions as $nr => $permission)}}
{{if(!is.empty($permission.uuid))}}
{{$permissions[$nr] = $permission.uuid}}
{{/if}}
{{/for.each}}
{{$permissions[] = $response.node.uuid}}
{{$role = R3m.Io.Node:Data:patch(
'Role',
$role,
[
'uuid' => $role.uuid,
'permission' => $permissions
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

