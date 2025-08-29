@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($client, array('route' => array('clients.store'), 'method' => 'POST')) }}

    @include('private/client/form_common', ['client'=>$client, 'users'=>$users])

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
