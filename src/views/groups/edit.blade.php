@extends(Config::get('Sentinel::config.layout'))

{{-- Web site Title --}}
@section('title')
@parent
Edit Group
@stop

{{-- Content --}}
@section('content')
<div class="row">
    <div class="col-md-4 col-md-offset-4">
	{{ Form::open(array('action' =>  array('Sentinel\GroupController@update', $group->id), 'method' => 'put')) }}
        <h2>Edit Group</h2>
    
        <div class="form-group {{ ($errors->has('name')) ? 'has-error' : '' }}">
            {{ Form::label('name', 'Name') }}
            {{ Form::text('name', $group->name, array('class' => 'form-control', 'placeholder' => 'Name')) }}
            {{ ($errors->has('name') ? $errors->first('name') : '') }}
        </div>

        <div class="form-group {{ ($errors->has('name')) ? 'has-error' : '' }}">
            {{ Form::label('slug', 'Slug') }}
            {{ Form::text('slug', $group->slug, array('class' => 'form-control', 'placeholder' => 'Slug')) }}
            {{ ($errors->has('slug') ? $errors->first('slug') : '') }}
        </div>

        {{ Form::label('Permissions') }}
        @foreach ($permissionLevels as $permission)
            <div class="checkbox">
              <label>
                {{ Form::checkbox("permissions[$permission]", true, array_key_exists($permission, $groupPermissions) ? $groupPermissions[$permission] : false ) }}
                {{ ucfirst($permission) }}
              </label>
            </div>
        @endforeach
        
        {{ Form::hidden('id', $group->id) }}
        {{ Form::submit('Save Changes', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}
    </div>
</div>

@stop