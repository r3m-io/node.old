{{R3M}}
{{Node:Setup:install()}}

Utilising the Node namespace:

{{literal}}
{{Node.Data.create($class, [
'attribute': 'string'
])}}
{{Node.Data.read($class, [
'uuid': 'string'
])}}
{{Node.Data.update($class, [
'uuid': 'string'
])}}
{{Node.Data.delete($class, [
'uuid': 'string'
])}}
{{Node.Data.list($class, [
'order' => [
    'id' => 'DESC'
],
'filter' => [
    'property[partial]' => ...
],
'limit' => 20,
'page' => 1
])}}
{{Node.Data.page($class, [
'order' => [
    'id' => 'DESC'
],
'filter' => [
    'property[partial]' => ...
],
'limit' => 20,
'page' => 1
])}}
{{Node.Data.import($class, [


])}}
{{Node.Data.export($class, [


])}}
{{Node.Data.backup($class, [


])}}
{{Node.Data.restore($class, [


])}}
{{/literal}}
