{{R3M}}
{{$request = request()}}
{{d($request)}}
Package: {{$request.package}}

Module: {{$request.module|uppercase.first}}

{{if(!is.empty($request.submodule))}}
Submodule: {{$request.submodule|uppercase.first}}
{{/if}}
{{dd($request)}}
{{if($request.module === 'info')}}
Commands:
{{binary()}} {{$request.package}} object drop
{{binary()}} {{$request.package}} object export
{{binary()}} {{$request.package}} object import
{{binary()}} {{$request.package}} object info
{{binary()}} {{$request.package}} object rename
{{binary()}} {{$request.package}} object sync
{{binary()}} {{$request.package}} object truncate
{{else}}
{{$options = options()}}
{{$is.all = false}}
{{if(is.empty.object($options))}}
{{$is.all = true}}
{{$files = dir.read(config('controller.dir.view') + 'Object/Info/')}}
{{$files = data.sort($files, ['url' => 'ASC'])}}
Options:
{{for.each($files as $file)}}
{{if($file.name === 'Object.Info.tpl')}}
{{continue()}}
{{/if}}
{{$file.basename = file.basename($file.name, config('extension.tpl'))}}
{{if(!is.empty($options[$file.basename|lowercase]) || !is.empty($is.all))}}
{{binary()}} {{$request.package}} {{$request.module}} {{$request.submodule}} -{{$file.basename|lowercase}}

{{/if}}
{{/for.each}}
{{else}}
{{$files = dir.read(config('controller.dir.view') + 'Object/Info/')}}
{{$files = data.sort($files, ['url' => 'ASC'])}}
{{for.each($files as $file)}}
{{if($file.name === 'Object.Info.tpl')}}
{{continue()}}
{{/if}}
{{$file.basename = file.basename($file.name, config('extension.tpl'))}}
{{if(!is.empty($options[$file.basename|lowercase]) || !is.empty($is.all))}}
{{require($file.url)}}
{{/if}}
{{/for.each}}
{{/if}}
{{/if}}