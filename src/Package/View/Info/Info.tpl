{{R3M}}
{{$package = 'r3m_io/node'}}
{{$module = 'object'}}
{{$submodule = 'info'}}
Package: {{$package}}

Module: {{$module|uppercase.first}}

Submodule: {{$submodule|uppercase.first}}

Commands:
{{binary()}} {{$package}} {{$module}} drop
{{binary()}} {{$package}} {{$module}} export
{{binary()}} {{$package}} {{$module}} import
{{binary()}} {{$package}} {{$module}} info
{{binary()}} {{$package}} {{$module}} rename
{{binary()}} {{$package}} {{$module}} sync
{{binary()}} {{$package}} {{$module}} truncate

/*
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
{{binary()}} {{$package}} {{$module}} {{$submodule}} -{{$file.basename|lowercase}}

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
*/