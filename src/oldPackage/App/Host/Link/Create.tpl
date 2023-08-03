{{R3M}}
{{$class = 'App.Host.Link'}}
{{$options = options()}}
{{if(
is.set($options.host) &&
is.set($options.link)
)}}
{{else}}
Create Host.Link:
{{$options.host = terminal.readline('Host: (including :port) ')}}
{{$options.link = terminal.readline('Link: ')}}
{{$options.extension = terminal.readline('Extension: ')}}
{{/if}}
{{if(is.empty($options.subdomain))}}
{{$options.subdomain = false}}
{{$options.name = controller.name($options.domain + '.' + $options.extension)}}
{{else}}
{{$options.name = controller.name($options.subdomain + '.' + $options.domain + '.' + $options.extension)}}
{{/if}}
{{if(!is.set($options['url-development']))}}
{{$options['url-development'] = terminal.readline('Url (development): ')}}
{{/if}}
{{if(!is.set($options['url-staging']))}}
{{$options['url-staging'] = terminal.readline('Url (staging): ')}}
{{/if}}
{{if(!is.set($options['url-production']))}}
{{$options['url-production'] = terminal.readline('Url (production): ')}}
{{/if}}
{{$response = R3m.Io.Node:Data:create(
$class,
R3m.Io.Node:Role:role_system(),
[
'name' => $options.name,
'subdomain' => $options.subdomain,
'domain' => $options.domain,
'extension' => $options.extension,
'url' => [
'development' => $options['url-development'],
'staging' => $options['url-staging'],
'production' => $options['url-production'],
],
'#class' => $class,
])}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}

