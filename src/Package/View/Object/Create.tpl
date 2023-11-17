/*
Need Class
Dir::create config project.dir.data + /Node/BinaryTree/{{$class}}/Asc
Touch File config project.dir.data + /Node/BinaryTree/{{$class}}/Asc/Uuid.btree
Touch File config project.dir.data + /Node/Object/{{$class}}.json

question property:
- ask name
- ask type (string, array, int, float, boolean, object)
- ask property
     |      |
     Y      N

Y:
* in sub: property ( array [] )

- ask name
- ask type (string, array, int, float, boolean, object)
- ask property
     |      |
     Y      N

N:
* in parent property ( array [] )

- ask name
- ask type (string, array, int, float, boolean, object)
- ask property
|      |
Y      N

wat r3m_io/node object create -class=System.Output.Filter
while(true)
name:
type:
property (y/n):
if(y)
while(true)
name:
type:
property (y/n):
if(y)
*/

{{$request = request()}}
{{$options = options()}}
{{$class = data.extract('options.class')}}
{{if(is.empty($class))}}
You need to provide the option class for the new class name.
{{else}}
{{dd($class)}}
/*
{{$response = R3m.Io.Node:Object:create(
$class,
R3m.Io.Node:Role:role_system(),
$options
)}}
{{$response|json.encode:'JSON_PRETTY_PRINT'}}
*/
{{/if}}