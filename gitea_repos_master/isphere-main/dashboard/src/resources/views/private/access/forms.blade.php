@extends('layouts.app-private')

@section('content')

    <a href="{{ route('accesses.edit', ['access' => $access->Level]) }}">Редактировать доступ "{{$access->Name}}"</a>
    <br/>
    <br/>

    @foreach($forms as $form)
        <div class="row mt-4 mb-2">
            <div class="col-12"><h3>{{ $form->Label }}</h3></div>
        </div>

        <div class="row mb-2">
            <div class="col-4">
                <b>Поле</b>
            </div>

            <div class="col-6">
                <b>Объект</b>
            </div>
        </div>

        @foreach($form->fields as $field)
        <div class="row mt-1">
            <div class="col-4">
                {{$field->Label}} ({{$field->Name}})
            </div>

            <div class="col-6">
                @foreach($field->objects as $object)
                    <input class="form-check-input relation-checkbox" type="checkbox" data-object="{{$object->Code}}" data-field="{{$field->Code}}" @if(!isset($fieldsMap[$field->Code][$object->Code])) checked @endif > {{$object->Code}} ({{$object->Label}})<br/>
                @endforeach
            </div>

            <div class="col-10">
                <hr/>
            </div>
        </div>

        @endforeach

    @endforeach

    <script type="text/javascript">

        $('.relation-checkbox').change(function(  ) {
            $(this).attr('disabled', 'disabled');

            var checkbox = $(this);

            $.post({
                url: "/private/accesses/{{$access->Level}}/forms/hide-field",
                data: {
                    'objectCode' : $(this).data('object'),
                    'fieldCode' : $(this).data('field'),
                    'hide' : $(this).is(':checked') ? 0 : 1,
                },
                error : function( data ) {
                    alert('Ошибка');
                },
                success : function( data ) {
                    checkbox.removeAttr('disabled');
                }
            });
        });

    </script>

@endsection
