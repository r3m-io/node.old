{{R3M}}
{{$class = 'Permission'}}
Create {{$class}}:
{{$name = terminal.readline('Name: ')}}
{{while(true)}}
{{$role = terminal.readline('Role: ')}}
{{$role = R3m.io.Node:Data:record('Role', [
'filter' => [
    'name' => $role
],
'sort' => [
    'name' => 'ASC'
]
])}}
{{dd($role)}}
{{if(empty($attribute))}}
{{break()}}
{{else}}
{{$attributes[] = $attribute}}
{{/if}}
{{/while}}

{{while(true)}}
{{$attribute = terminal.readline('Attribute: ')}}
{{if(empty($attribute))}}
{{break()}}
{{else}}
{{$attributes[] = $attribute}}
{{/if}}
{{/while}}
{{$response = R3m.Io.Node:Data:create("{{$class}}", [
'name' => $name,
'attributes' => $attributes,
'role' => $role
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

