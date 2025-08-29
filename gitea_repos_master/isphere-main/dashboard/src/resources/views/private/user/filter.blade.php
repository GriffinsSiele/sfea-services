<span class="align-middle py-1" style="height: 100%; display: inline-block;">
    <input type="checkbox" class="form-check-input" name="filters[userExpired]" role="grid-view-filter-item" @if (isset(app('request')->input('filters')['userExpired'])) checked @endif />&nbsp;<b>Истёк срок доступа</b>
</span>
