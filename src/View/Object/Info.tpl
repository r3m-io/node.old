{{R3M}}
{{$options = options()}}
{{$is.all = false}}
{{if(empty($options))}}
{{$is.all = true}}
{{/if}}
{{if(!empty($options.create) || !empty($is.all))}}
{{dd('{{$this}}')}}
## Create


{{/if}}
{{if(!empty($options.create) || !empty($is.all))}}
## Create


{{/if}}
{{if(!empty($options.create) || !empty($is.all))}}
## Create


{{/if}}
{{if(!empty($options.create) || !empty($is.all))}}
## Create


{{/if}}
{{if(!empty($options.create) || !empty($is.all))}}
## Create


{{/if}}
{{if(!empty($options.create) || !empty($is.all))}}
## Create


{{/if}}


## Read

## Patch

## Put

## Delete

## Import

## Export

## List

## Drop

## Truncate

## Clear
Clears the filter & where.
#### Options:
- -class="..., ..." - comma separated list of classes to clear
- -force - force clear

## Sync
Sync lists of objects.
#### Options:
- -class="..., ..." - comma separated list of classes to sync

