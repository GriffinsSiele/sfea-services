    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('code', 'Код') }}
            {{ Form::text('code', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('title', 'Название') }}
            {{ Form::text('title', null, ['class' => 'form-control']) }}
        </div>
    </div>

    

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('status', 'Статус') }}
            {{ Form::select('status', \App\Models\CheckType::$statusMap, $item->status, ['class' => 'form-select ct-status-select']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('plugin', 'Плагин') }}
            {{ Form::text('plugin', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('queue', 'Очередь') }}
            {{ Form::text('queue', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('hash', 'Хэш') }}
            {{ Form::text('hash', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            <br/>
            {{ Form::hidden('person',0) }}
            {{ Form::checkbox('person', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('person', 'Физ лицо', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::hidden('org',0) }}
            {{ Form::checkbox('org', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('org', 'Организация', ['class'=>'form-check-label']) }}

        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::hidden('phone',0) }}
            {{ Form::checkbox('phone', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('phone', 'Телефон', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::hidden('email',0) }}
            {{ Form::checkbox('email', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('email', 'Email', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::hidden('url',0) }}
            {{ Form::checkbox('url', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('url', 'URL', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::hidden('auto',0) }}
            {{ Form::checkbox('auto', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('auto', 'Авто', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::hidden('ip',0) }}
            {{ Form::checkbox('ip', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('ip', 'IP', ['class'=>'form-check-label']) }}
        </div>
    </div>
    

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::hidden('nick',0) }}
            {{ Form::checkbox('nick', 1, null, ['class' => 'form-check-input']) }}
            {{ Form::label('nick', 'НИК', ['class'=>'form-check-label']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            <hr/>
            {{ Form::label('source_code', 'Код источника') }}
            {{ Form::text('source_code', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('source_name', 'Название источника (source_name)') }}
            {{ Form::text('source_name', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('source_title', 'Название источника (source_title)') }}
            {{ Form::text('source_title', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('source_formtitle', 'Название источника (форма)') }}
            {{ Form::text('source_formtitle', null, ['class' => 'form-control']) }}
            <hr/>
        </div>
    </div>