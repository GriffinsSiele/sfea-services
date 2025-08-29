@extends('layouts.app-private')

@section('content')

    {{ Html::ul($errors->all()) }}

    {{ Form::model($item, array('route' => array('proxies.store'), 'method' => 'POST')) }}

    @include('private/proxy/form_common', ['item'=>$item])

    <div class="row">
        <div class="col-12 col-lg-6 pt-3">
        {{ Form::submit('Создать', array('class' => 'btn btn-primary')) }}
        </div>
    </div>

    {{ Form::close() }}

@endsection
