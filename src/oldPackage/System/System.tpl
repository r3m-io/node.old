{{R3M}}
{{$package = 'r3m-io/node'}}
{{$category = 'system'}}
{{$modules = [
'setup',
]}}
Package: {{$package}}

Category: {{$category|uppercase.first}}

{{for.each($modules as $module)}}
{{binary()}} {{$package}} {{$category}} {{$module}}

{{/for.each}}

