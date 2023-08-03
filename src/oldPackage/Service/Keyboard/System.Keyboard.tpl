{{$package = 'r3m-io/node'}}
{{$category = 'system'}}
{{$module = 'keyboard'}}
{{$submodules = [
'create',
'delete',
'export',
'import',
'info',
'list',
'read',
'update'
]}}
Package: {{$package}}

Category: {{$category|uppercase.first}}

{{for.each($submodules as $submodule)}}
{{binary()}} {{$package}} {{$category}} {{$module}} {{$submodule}}

{{/for.each}}

