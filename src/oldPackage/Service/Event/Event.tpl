{{R3M}}
{{$package = 'r3m-io/node'}}
{{$category = 'event'}}
{{$modules = [
'create',
'delete',
'export',
'import',
'info',
'list',
'read',
'update',
]}}
Package: {{$package}}

Category: {{$category|uppercase.first}}

{{for.each($modules as $module)}}
{{binary()}} {{$package}} {{$category}} {{$module}}

{{/for.each}}

