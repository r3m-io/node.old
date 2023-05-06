{{R3M}}
{{$class = 'Permission'}}
Create {{$class}}:
{{$name = string.trim(terminal.readline('Name: '))}}
{{while(true)}}
{{$role = string.trim(terminal.readline('Role: '))}}
{{$role = R3m.Io.Node:Data:record('Role', [
'where' => [
[
'attribute' => 'name',
'value' => $role,
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
{{$response = R3m.Io.Node:Data:create("{{$class}}", [
'name' => $name,
'attributes' => $attributes,
'role' => $role.uuid
])}}
{{if(is.empty($role.permission))}}
{{$role.permission = []}}
{{/if}}
{{$role.permission[] = $response.node.uuid}}
{{$role = R3m.Io.Node:Data:patch('Role', [
'uuid' => $role.uuid,
'permission' => $role.permission
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

