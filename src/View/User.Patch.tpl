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
Update User:

{{/if}}
{{$response = R3m.Io.Node:Data:list('User', [
'order' => [
'email' => 'ASC'
],
'limit' => (int) $options.limit,
'page' => (int) $options.page,
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
{{$users = $options.node}}
{{if(is.empty($users))}}
{{$users = terminal.readline('User: ')}}
{{/if}}
{{$users = preg_replace('/\s+/', ' ', $users)}}
{{$users = string.replace(', ', ',', $users)}}
{{if(string.contains.case.insensitive($users, 'all'))}}
{{$users = $response.list}}
{{else}}
    {{$users = explode(',', $users)}}
    {{for.each($users as $nr => $selector)}}
        {{$selector = (int) $selector}}
        {{if(array.key.exist($selector - 1, $response.list))}}
            {{$users[$nr] = $response.list[$selector - 1]}}
        {{/if}}
    {{/for.each}}
{{/if}}
{{if(is.array($users))}}
    {{for.each($users as $nr => $user)}}
        {{$patch.uuid = $user.uuid}}
        {{$patch = R3m.Io.Node:Data:read('User', [
            'uuid' => $patch.uuid
        ])}}
        {{if($options.email)}}
            {{$patch.email = $options.email}}
        {{/if}}
        {{if(
        $options.password &&
        $options.password_repeat &&
        $options.password === $options.password_repeat
        )}}
        {{$patch.password = password.hash($options.password, 13)}}
        {{/if}}
        {{if($options.role)}}
            {{if(!$options.role_page)}}
                {{$options.role_page = 1}}
            {{/if}}
            {{if(!$options.role_limit)}}
                {{$options.role_limit = 255}}
            {{/if}}
        {{/if}}
        {{$response = R3m.Io.Node:Data:list('Role', [
        'order' => [
        'rank' => 'ASC',
        'name' => 'ASC'
        ],
        'limit' => (int) $options.role_limit,
        'page' => (int) $options.role_page,
        ])}}
        {{$roles = $options.role}}
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
            {{$list = R3m.Io.Node:Data:list_attribute($roles, ['uuid', 'name', 'rank'])}}
            {{for.each($list as $patch_nr => $patch_role)}}
                {{for.each($patch.Role as $nr => $role)}}
                    {{if($role.uuid === $patch_role.uuid)}}
                        {{$patch.Role[$nr] = $patch_role}}
                    {{/if}}
                {{/for.each}}
            {{/for.each}}
        {{/if}}
        {{$response = R3m.Io.Node:Data:patch('User', $patch)}}
        {{$response|json.encode:'JSON_PRETTY_PRINT'}}
    {{/for.each}}
{{/if}}

