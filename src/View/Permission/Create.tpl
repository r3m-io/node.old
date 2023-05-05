{{R3M}}
{{$class = 'Permission'}}
Create {{$class}}:
{{$name = string.trim(terminal.readline('Name: '))}}
{{while(true)}}
{{$role = terminal.readline('Role: ')}}
{{$role = R3m.io.Node:Data:record('Role', [
'filter' => [
    'name' => $role|trim
],
'sort' => [
    'name' => 'ASC'
]
])}}
{{dd($role)}}
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
'role' => $role
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

