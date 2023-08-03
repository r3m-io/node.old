{{R3M}}
{{$package = 'r3m-io/node'}}
{{$read = object(dir.read(config('controller.dir.view')), 'object')}}
{{for.each($read as $nr => $file)}}
{{if($file.type !== 'Dir')}}
{{data.delete('read.' + $nr)}}
{{/if}}
{{/for.each}}
{{$read = data.sort($read, ['url' => 'ASC'])}}
{{$categories = []}}
{{for.each($read as $file)}}
{{if(string.lowercase($file.name) === 'info')}}
{{continue()}}
{{/if}}
{{$categories[] = $file.name}}
{{/for.each}}
{{for.each($categories as $category)}}
{{$url = config('controller.dir.view') + $category + config('ds') + $category + config('extension.tpl')}}
{{if(file.exist($url))}}
{{require($url)}}
{{/if}}
{{/for.each}}

