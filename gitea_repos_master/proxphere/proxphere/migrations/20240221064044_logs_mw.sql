-- +goose Up
-- +goose StatementBegin
create materialized view proxy_spec_logs_mw
to proxy_spec_logs
as
select *
from proxy_spec_logs_queue;
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
drop view proxy_spec_logs_mw;
-- +goose StatementEnd
