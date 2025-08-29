
    <div class="row">
        <div class="col-12 col-lg-6">
        {{ Form::label('Name', 'Название') }}
        {{ Form::text('Name', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('OfficialName', 'Юридическое название') }}
            {{ Form::text('OfficialName', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Status', 'Статус') }}
            {{ Form::select('Status', [''=>'Не задано'] + \App\Models\Client::$statusMap, $client->Status, ['class' => 'form-select client-status', 'data-client-id'=>$client->id]) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Code', 'Код') }}
            {{ Form::text('Code', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('TariffId', 'Тариф') }}
            {{ Form::select('TariffId', $tariffs, $client->TariffId ?? env('CLIENT_DEFAULT_TARIFF'), ['class' => 'form-select']) }}
        </div>
    </div>

    @php
        $phones = [];
        for($count = 0; $count<4; $count++)
            $phones[] = $client->phones[$count] ?? new \App\Models\Phone();
    @endphp

    @foreach ($phones as $ind => $phone)
        <div class="row phone-row">
            <div class="col-24">
                {{ Form::label(null, 'Телефон') }}
            </div>

            <div class="col-12 col-lg-6">
                <div class="input-group">
                    <input class="form-control phone-number" name="Phones[{{$ind}}][Number]" type="text" placeholder="Номер" value="{{$phone->Number}}">
                    <span class="input-group-btn" style="width:0px;"></span>
                    <input class="form-control" name="Phones[{{$ind}}][InnerCode]" type="text" placeholder="Добавочный" value="{{$phone->InnerCode}}">

                    <input type="hidden" name="Phones[{{$ind}}][Id]" value="{{$phone->Id}}">
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <input class="form-control" name="Phones[{{$ind}}][Notice]" type="text" placeholder="Примечание"  value="{{$phone->Notice}}">
            </div>
        </div>
    @endforeach

    {{--
    <div class="row">
        <div class="col-12 col-lg-6">
            <hr/>
            {{ Form::label('Phone', 'Телефон') }}
            {{ Form::text('Phone', null, ['class' => 'form-control']) }}
        </div>
    </div>
    --}}

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Email', 'Почта') }}
            {{ Form::Email('Email', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Address', 'Адрес') }}
            {{ Form::text('Address', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            <hr/>
            {{ Form::label('INN', 'ИНН') }}
            {{ Form::text('INN', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('OGRN', 'ОГРН') }}
            {{ Form::text('OGRN', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('KPP', 'КПП') }}
            {{ Form::text('KPP', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('BIK', 'БИК') }}
            {{ Form::text('BIK', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('Bank', 'Банк') }}
            {{ Form::text('Bank', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('BankAccount', 'Р/С') }}
            {{ Form::text('BankAccount', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('ContactName', 'Контактное лицо по договору') }}
            {{ Form::text('ContactName', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('ContractNum', 'Номер договора') }}
            {{ Form::text('ContractNum', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('ContractStartDate', 'Дата заключения договора') }}
            {{ Form::date('ContractStartDate', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('ContractStopDate', 'Дата расторжения договора') }}
            {{ Form::date('ContractStopDate', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('TariffStartDate', 'Дата начала тарификации') }}
            {{ Form::date('TariffStartDate', null, ['class' => 'form-control']) }}
        </div>
    </div>

    @can('use-function','clients_all')
    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('MasterUserId', 'Основной пользователь') }}
            {{ Form::select('MasterUserId', $users, $client->MasterUserId, ['class' => 'form-select']) }}
        </div>
    </div>
    @endcan

    @can('use-function','clients_all')
    <div class="row">
        <div class="col-12 col-lg-6">
            <hr/>
            {{ Form::label('StartTime', 'Дата/время начала доступа') }}
            {{ Form::datetimeLocal('StartTime', null, ['class' => 'form-control']) }}
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-6">
            {{ Form::label('EndTime', 'Дата/время окончания доступа') }}
            {{ Form::datetimeLocal('EndTime', null, ['class' => 'form-control']) }}
        </div>
    </div>
    @endcan


    <script type="text/javascript">

        function hideEmptyPhones() {
            $('.phone-row').each(function( index ) {
                if($(this).find('.phone-number').val() == '')
                    $(this).hide();
            });

            $('.phone-row:hidden').first().show();
        }

        hideEmptyPhones();

        $('.client-status').change(function() {

            let clientId = $(this).data('client-id');
            if(!clientId)
                return;

            var selected = $(this).val();
            var lastVal = $.data(this, 'current');

            if (!['1','4'].includes(selected)) { // Если выбрали не тестирование и действующий
                $('.client-status').attr('disabled', 'disabled');

                $.ajax({
                    url: "/private/clients/" + clientId + "/last-day-requests-count",
                    dataType: 'json',
                    success : function( data ) {
                        $('.client-status').removeAttr('disabled');
                        if (data.requestsCount && !confirm('От клиента поступило ' + data.requestsCount + ' запросов за вчера и сегодня, после изменения статуса новые запросы будут невозможны. Изменить?')) {
                            $('.client-status').val(lastVal);
                            return false;
                        }
                    }
                });
            }

            $.data(this, 'current', $(this).val());
        });

        $('.phone-number').change(hideEmptyPhones);

    </script>