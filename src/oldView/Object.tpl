{{R3M}}
{{$package = 'r3m-io/node'}}
{{$category = 'object'}}
{{$modules = [
'clear',
'create',
'drop',
'export',
'expose',
'import',
'info',
'list',
'patch',
'put',
'read',
'rename',
'sync',
'truncate',
'validate'
]}}
Package: {{$package}}

Category: {{$category|uppercase.first}}

{{for.each($modules as $module)}}
{{binary()}} {{$package}} {{$category}} {{$module}}

{{/for.each}}

