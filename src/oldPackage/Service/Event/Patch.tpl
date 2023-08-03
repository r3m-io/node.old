{{R3M}}
{{$options = options()}}
{{if(!$options.page)}}
{{$options.page = 1}}
{{/if}}
{{if(!$options.limit)}}
{{$options.limit = 255}}
{{/if}}
{{if($options.format === 'json')}}
{{else}}
Update Event:

{{/if}}
{{$response = R3m.Io.Node:Data:list(
'App.Event',
[
'sort' => [
'options.priority' => 'ASC',
'action' => 'ASC'
],
'#where' => '
(
    options.priority === 1
    xor
    (
        options.priority === 11
        and
        action === "yyy"
    )
    xor
    (
        options.priority === 12
        and
        action === "utyrrt"
    )
)',
'filter' => [
    'action' => [
    'value' => 'yyy',
    'operator' => '==='
    ]
],
'limit' => (int) $options.limit,
'page' => (int) $options.page
])}}
{{dd($response)}}

{{if(is.array($response.list))}}
{{for.each($response.list as $nr => $role)}}
{{$selector = $nr + 1}}
[{{$selector}}] {{$role.name}} ({{$role.rank}})
{{/for.each}}
{{/if}}
{{$roles = $options.role}}
{{if(is.empty($roles))}}
{{$roles = terminal.readline('Role: ')}}
{{/if}}
{{$roles = preg_replace('/\s+/', ' ', $roles)}}
{{$roles = string.replace(', ', ',', $roles)}}
{{if(string.contains.case.insensitive($roles, 'all'))}}
{{$roles = $response.list}}
{{else}}
{{$roles = explode(',', $roles)}}
{{for.each($roles as $nr => $selector)}}
{{$selector = (int) $selector}}
{{if(array.key.exist($selector - 1, $response.list))}}
{{$roles[$nr] = $response.list[$selector - 1]}}
{{/if}}
{{/for.each}}
{{/if}}
{{if(is.array($roles))}}
{{for.each($roles as $nr => $role)}}
{{$patch.uuid = $role.uuid}}
{{if($options.rank)}}
{{$patch.rank = (int) $options.rank}}
{{/if}}
{{if($options.name)}}
{{$patch.name =  $options.name}}
{{/if}}
{{$response = R3m.Io.Node:Data:patch('Role', $patch)}}
{{/for.each}}
{{/if}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

