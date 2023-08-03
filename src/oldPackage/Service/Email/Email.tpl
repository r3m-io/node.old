{{$package = 'r3m-io/node'}}
{{$category = 'email'}}
{{$modules = [
'attachment',
'create',
'delete',
'list',
'queue',
'read',
'update',
]}}
Package: {{$package}}

Category: {{$category|uppercase.first}}

{{for.each($modules as $module)}}
{{binary()}} {{$package}} {{$category}} {{$module}}

{{/for.each}}

