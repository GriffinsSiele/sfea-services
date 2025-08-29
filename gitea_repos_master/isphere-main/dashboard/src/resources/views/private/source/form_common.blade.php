    <div class="row">
        <div class="col-12 col-lg-6">
            <h2>{{$item->code}}</h2>

            {{ Form::label('name', 'Название') }}
            {{ Form::text('name', null, ['class' => 'form-control']) }}
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