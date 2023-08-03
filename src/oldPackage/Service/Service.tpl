{{R3M}}
{{$package = 'r3m-io/node'}}
{{$category = 'service'}}
{{$modules = [
'email',
'event',
'keyboard',
'middleware',
'output filter',
'route',
'task'
]}}
Package: {{$package}}

Category: {{$category|uppercase.first}}

{{for.each($modules as $module)}}
{{binary()}} {{$package}} {{$category}} {{$module}}

{{/for.each}}

