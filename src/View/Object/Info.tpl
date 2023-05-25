{{R3M}}
{{$options = options()}}
{{$is.all = false}}
{{if(is.empty($options))}}
{{$is.all = true}}
{{/if}}

{{$files = dir.read(config('controller.dir.view') + 'Object/Info/')}}
{{$files = data.sort($files, ['url' => 'ASC'])}}
{{for.each($files as $file)}}
    {{$file.basename = file.basename($file.name, config('extension.tpl'))}}
    {{$option = $options[$file.basename|lowercase]}}
    {{d($options)}}
    {{d($file.basename)}}
    {{dd($option)}}
{{/for.each}}
{{dd($files)}}

{{if(!is.empty($options.create) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Create.tpl')}}
{{/if}}
{{if(!is.empty($options.read) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Read.tpl')}}
{{/if}}
{{if(!is.empty($options.patch) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Patch.tpl')}}
{{/if}}
{{if(!is.empty($options.put) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Put.tpl')}}
{{/if}}
{{if(!is.empty($options.delete) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Delete.tpl')}}
{{/if}}
{{if(!is.empty($options.import) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Import.tpl')}}
{{/if}}
{{if(!is.empty($options.export) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Export.tpl')}}
{{/if}}
{{if(!is.empty($options.list) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/List.tpl')}}
{{/if}}
{{if(!is.empty($options.drop) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Drop.tpl')}}
{{/if}}
{{if(!is.empty($options.truncate) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Truncate.tpl')}}
{{/if}}
{{if(!is.empty($options.clear) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Clear.tpl')}}
{{/if}}
{{if(!is.empty($options.sync) || !is.empty($is.all))}}
{{require(config('controller.dir.view') + 'Object/Info/Sync.tpl')}}
{{/if}}
























