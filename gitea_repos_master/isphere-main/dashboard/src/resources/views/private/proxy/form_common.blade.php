    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('server', 'Сервер') }}
            {{ Form::text('server', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('port', 'Порт') }}
            {{ Form::text('port', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('login', 'Логин') }}
            {{ Form::text('login', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('password', 'Пароль') }}
            {{ Form::text('password', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('country', 'Страна') }}
            {{ Form::select('country', array_merge([''=>''], array_combine(\App\Models\Proxy::$countriesList, \App\Models\Proxy::$countriesList)), $item->country, ['class' => 'form-select']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('comment', 'Комментарий') }}
            {{ Form::text('comment', null, ['class' => 'form-control']) }}
        </div>
    </div>
    
    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('proxygroup', 'Группа прокси') }}
            {{ Form::text('proxygroup', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('rotation', 'Ротация') }}
            {{ Form::text('rotation', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            <br/>
            {{ Form::hidden('enabled',0) }}
            {{ Form::checkbox('enabled', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('enabled', 'Включено', ['class'=>'form-check-label']) }}
        </div>
    </div>