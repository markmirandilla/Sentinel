@extends(Config::get('Sentinel::config.layout'))

{{-- Web site Title --}}
@section('title')
@parent
Reset Password
@stop

{{-- Content --}}
@section('content')
<div class="row">
    <div class="col-md-4 col-md-offset-4">
        {{ Form::open() }}
            
            <h2>Reset Password</h2>
            
            <div class="form-group {{ ($errors->has('password')) ? 'has-error' : '' }}">
                {{ Form::password('password', array('class' => 'form-control', 'placeholder' => 'New Password')) }}
                {{ ($errors->has('password') ?  $errors->first('password') : '') }}
            </div>

            <div class="form-group {{ ($errors->has('password_confirmation')) ? 'has-error' : '' }}">
                {{ Form::password('password_confirmation', array('class' => 'form-control', 'placeholder' => 'Confirm New Password')) }}
                {{ ($errors->has('password_confirmation') ?  $errors->first('password_confirmation') : '') }}
            </div>

            {{ Form::submit('Go', array('class' => 'btn btn-primary'))}}

  		{{ Form::close() }}
  	</div>
</div>

@stop