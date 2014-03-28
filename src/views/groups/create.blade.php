@extends(Config::get('Sentinel::config.layout'))

{{-- Web site Title --}}
@section('title')
@parent
Create Group
@stop

{{-- Content --}}
@section('content')
<div class="row">
    <div class="col-md-4 col-md-offset-4">
	{{ Form::open(array('action' => 'Sentinel\GroupController@store')) }}
        <h2>Create New Group</h2>

        <div class="form-group {{ ($errors->has('name')) ? 'has-error' : '' }}">
            {{ Form::label('name', 'Name') }}
            {{ Form::text('name', null, array('class' => 'form-control', 'placeholder' => 'Name')) }}
            {{ ($errors->has('name') ? $errors->first('name') : '') }}
        </div>

        <div class="form-group {{ ($errors->has('name')) ? 'has-error' : '' }}">
            {{ Form::label('slug', 'Slug') }}
            {{ Form::text('slug', null, array('class' => 'form-control', 'placeholder' => 'Slug')) }}
            {{ ($errors->has('slug') ? $errors->first('slug') : '') }}
        </div>

        {{ Form::label('Permissions') }}
        @foreach ($permissionLevels as $permission)
            <div class="checkbox">
              <label>
                {{ Form::checkbox("permissions[$permission]", true) }}
                {{ ucfirst($permission) }}
              </label>
            </div>
        @endforeach

        {{ Form::submit('Create New Group', array('class' => 'btn btn-primary')) }}

    {{ Form::close() }}
    </div>
</div>

@stop