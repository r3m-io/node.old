{{R3M}}
{{$package = 'r3m-io/node'}}
{{$category = 'output'}}
{{$module = 'filter'}}
{{$submodules = [
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

Category: {{$category|uppercase.first}} {{$module|uppercase.first}}

{{for.each($submodules as $submodule)}}
{{binary()}} {{$package}} {{$category}} {{$module}} {{$submodule}}

{{/for.each}}

