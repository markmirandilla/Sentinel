@extends(Config::get('Sentinel::config.layout'))

{{-- Web site Title --}}
@section('title')
@parent
View Group
@stop

{{-- Content --}}
@section('content')
<h4>{{ $group['name'] }} Group</h4>
<div class="well clearfix">
	<div class="col-md-10">
	    <strong>Permissions:</strong>
	    <ul>
	    	@foreach ($group->getPermissions()->getPermissions() as $key => $value)
	    		<li>{{ ucfirst($key) }}</li>
	    	@endforeach
	    </ul>
	</div>
	<div class="col-md-2">
		<button class="btn btn-primary" onClick="location.href='{{ action('Sentinel\GroupController@edit', array($group->id)) }}'">Edit Group</button>
	</div> 
</div>
<hr />
<h4>Group Object</h4>
<div>
    <pre>{{ var_dump($group) }}</pre>
</div>

@stop
