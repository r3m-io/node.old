{{$package = 'r3m-io/node'}}
{{$category = 'account'}}
{{$modules = [
'permission',
'role',
'user',
]}}
Package: {{$package}}

Category: {{$category|uppercase.first}}

{{for.each($modules as $module)}}
{{binary()}} {{$package}} {{$category}} {{$module}}

{{/for.each}}

