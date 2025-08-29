<span class="align-middle py-1" style="height: 30px; display: inline-block;">
    <input type="checkbox" class="form-check-input" role="grid-view-filter-item" name="filters[clientExpired]" @if (isset(app('request')->input('filters')['clientExpired'])) checked @endif /> <b>Истёк срок доступа</b>
</span>
