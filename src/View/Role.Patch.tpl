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
Update Role:

{{/if}}
{{$response = R3m.Io.Node:Data:list('Role', [
'order' => [
'rank' => 'ASC',
'name' => 'ASC'
],
'limit' => (int) $options.limit,
'page' => (int) $options.page,
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
{{if(is.array($roles))}}
{{for.each($roles as $nr => $role)}}
{{$patch = (clone) $options}}
{{$patch.uuid = $role.uuid}}
{{unset($patch.format)}}
{{unset($patch.page)}}
{{unset($patch.limit)}}
{{dd($patch)}}
{{if($patch.rank)}}
{{$patch.rank = (int) $patch.rank}}
{{$response = R3m.Io.Node:Data:patch('Role', $patch)}}
{{/for.each}}
{{/if}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

