@php
    /** @var \Itstructure\GridView\Columns\BaseColumn[] $columnObjects */
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    /** @var boolean $useFilters */
    $checkboxesExist = false;
@endphp
<style>
    .table-bordered tfoot tr td {
        border-width: 0;
    }
</style>

        @if($title)
            <h2 class="card-title">{!! $title !!}</h2>
        @endif

        <table class="table @if($tableBordered) table-bordered @endif @if($tableStriped) table-striped @endif @if($tableHover) table-hover @endif @if($tableSmall) table-sm @endif">
            <thead>
                <tr>
                    @foreach($columnObjects as $column_obj)
                        <th {!! $column_obj->buildHtmlAttributes() !!}>

                            @if($column_obj->getSort() === false || $column_obj instanceof \Itstructure\GridView\Columns\ActionColumn)
                                {{ $column_obj->getLabel() }}

                            @elseif($column_obj instanceof \Itstructure\GridView\Columns\CheckboxColumn)
                                @php($checkboxesExist = true)
                                @if($useFilters)
                                    {{ $column_obj->getLabel() }}
                                @else
                                    <input type="checkbox" id="grid_view_checkbox_main" class="form-check-input"  @if($paginator->count() == 0) disabled="disabled" @endif />
                                @endif

                            @else
                                <a href="{{ \Itstructure\GridView\Helpers\SortHelper::getSortableLink(request(), $column_obj) }}">{{ $column_obj->getLabel() }}</a>
                            @endif

                        </th>
                    @endforeach
                </tr>
                @if ($useFilters)

                    <form action="{{ $filtersFormAction }}" method="get" id="grid_view_filters_form">

                    <tr>
                            @foreach($columnObjects as $column_obj)
                                <td class="text-center align-middle">
                                    @if($column_obj instanceof \Itstructure\GridView\Columns\CheckboxColumn)
                                        <input type="checkbox" id="grid_view_checkbox_main" class="form-check-input" @if($paginator->count() == 0) disabled="disabled" @endif />
                                    @else
                                        {!! $column_obj->getFilter()->render() !!}
                                    @endif
                                </td>
                            @endforeach
                            <input type="submit" class="d-none">

                    </tr>

                    <tr>
                        <td></td>
                        <td colspan="{{ count($columnObjects)-1 }}">

                            @if (isset($additionalFiltersTpl))
                                @include($additionalFiltersTpl)
                            @endif

                            <span class="float-end">
                                <button id="grid_view_search_button" type="button" class="btn btn-primary">{{ $searchButtonLabel }}</button>
                                <button id="grid_view_reset_button" type="button" class="btn btn-warning">{{ $resetButtonLabel }}</button>
                            </span>
                        </td>
                    </tr>

                    </form>
                @endif
            </thead>

            <form action="{{ $rowsFormAction }}" method="post" id="grid_view_rows_form">
                <tbody>
                    @foreach($paginator->items() as $key => $row)
                        <tr>
                            @foreach($columnObjects as $column_obj)
                                <td>{!! $column_obj->render($row) !!}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <td colspan="{{ count($columnObjects) }}">
                            <div class="mt-3">
                                <div class="row">
                                    <div class="col-12 col-xl-8">
                                        <div class="float-right">
                                            @if ($paginator->onFirstPage())
                                                {!! trans('grid_view::grid.page-info', [
                                                    'start' => '<b>' . min(1, $paginator->total()) . '</b>',
                                                    'end' => '<b>' . min($paginator->perPage(), $paginator->total()) . '</b>',
                                                    'total' => '<b>' . $paginator->total() . '</b>',
                                                ]) !!}
                                            @elseif ($paginator->currentPage() == $paginator->lastPage())
                                                {!! trans('grid_view::grid.page-info', [
                                                    'start' => '<b>' . (($paginator->currentPage() - 1) * $paginator->perPage() + 1) . '</b>',
                                                    'end' => '<b>' . $paginator->total() . '</b>',
                                                    'total' => '<b>' . $paginator->total() . '</b>',
                                                ]) !!}
                                            @else
                                                {!! trans('grid_view::grid.page-info', [
                                                    'start' => '<b>' . (($paginator->currentPage() - 1) * $paginator->perPage() + 1) . '</b>',
                                                    'end' => '<b>' . (($paginator->currentPage()) * $paginator->perPage()) . '</b>',
                                                    'total' => '<b>' . $paginator->total() . '</b>',
                                                ]) !!}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-12 col-xl-8 text-center text-xl-left">
                                        {{ $paginator->render('grid_view::pagination') }}
                                    </div>

                                    {{--
                                    <div class="col-12 col-xl-4 text-center text-xl-right">

                                        @if (($checkboxesExist || $useSendButtonAnyway) && $paginator->count() > 0)
                                            <button type="submit" class="btn btn-danger">{{ $sendButtonLabel }}</button>
                                        @endif
                                    </div>
                                    --}}
                                </div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
                <input type="hidden" value="{!! csrf_token() !!}" name="_token">
            </form>
        </table>


<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {

        function initMassCheck() {
            var currentUrl = new URL(window.location.href);
            var massIds = (currentUrl.searchParams.get('mass[ids]') ?? '').split(',');
            var includeMode = currentUrl.searchParams.get('mass[mode]')=='include';

            $('#grid_view_checkbox_main').attr('checked', !includeMode);

            $('input[role="grid-view-checkbox-item"]').each(function () {
                let checkbox = $(this);
                checkbox.attr('checked', includeMode && massIds.includes(checkbox.val()) || !includeMode && !massIds.includes(checkbox.val()));
            });
        }

        function refreshPagesByMassCheck() {
            var currentUrl = new URL(window.location.href);

            $('.page-link').each(function () {
                if($(this).attr('href') && $(this).attr('href').length > 10) { // костыль
                    let href = new URL($(this).attr('href'));

                    href.searchParams.delete('mass[ids]');
                    href.searchParams.delete('mass[mode]');

                    href.searchParams.set("mass[mode]", currentUrl.searchParams.get('mass[mode]') ?? 'exclude');
                    href.searchParams.set("mass[ids]", currentUrl.searchParams.get('mass[ids]'));

                    $(this).attr('href', href);
                }
            });
        }

        // Если есть кнопка для "массовой" работы с записями
        if($('.grid-view-mass-check').length) {

            initMassCheck();

            $('.grid-view-mass-check').on('click', function () {
                let dlUrl = $(this).data('uri') + window.location.search;

                if($(this).hasClass('grid-view-excel-download')) {
                    window.open(dlUrl,"_blank");
                }
                else
                    window.location = dlUrl;
            });

            $('input[role="grid-view-checkbox-item"]').on('click',function (event) {

                var currentUrl = new URL(window.location.href);
                var massIds = (currentUrl.searchParams.get('mass[ids]') ?? '').split(',');

                let action = (
                    $('#grid_view_checkbox_main').attr('checked') && !event.target.checked
                    ||
                    !$('#grid_view_checkbox_main').attr('checked') && event.target.checked
                ) ? 'add' : 'rem';

                let val = $(this).val();

                if(action == 'add')
                    massIds.push(val);
                else
                    massIds = massIds.filter(function(item) {
                        return item !== val;
                    });

                currentUrl.searchParams.set('mass[ids]', massIds.join(','));

                window.history.replaceState({}, '', `${window.location.pathname}?${currentUrl.searchParams}`);
                refreshPagesByMassCheck();
            });

            $('#grid_view_checkbox_main').on('click',function (event) {

                $(this).attr('checked', event.target.checked);

                var currentUrl = new URL(window.location.href);

                currentUrl.searchParams.delete('mass[ids]');
                currentUrl.searchParams.delete('mass[mode]');

                if(event.target.checked)
                    currentUrl.searchParams.set('mass[mode]', 'exclude');
                else
                    currentUrl.searchParams.set('mass[mode]', 'include');

                console.log(`${window.location.pathname}?${currentUrl.searchParams}`);

                window.history.replaceState({}, '', `${window.location.pathname}?${currentUrl.searchParams}`);
                refreshPagesByMassCheck();
            });
        }

        $('#grid_view_checkbox_main').click(function (event) {
            $('input[role="grid-view-checkbox-item"]').prop('checked', event.target.checked);
        });

        $('#grid_view_search_button').click(function () {
            $('#grid_view_filters_form').submit();
        });

        $('#grid_view_reset_button').click(function () {
            $('input[role="grid-view-filter-item"]').val('');
            $('select[role="grid-view-filter-item"]').prop('selectedIndex', 0);
            $('#grid_view_filters_form').submit();
        });
    });
</script>