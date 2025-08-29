-- +goose Up
-- +goose StatementBegin
create table proxy_spec_logs
(
    `proxy_spec_id`        Int32,
    `proxy_spec_group_id`  Int32,
    `request_host`         LowCardinality(String),
    `response_status_code` Int16,
    `error`                Nullable(String),
    `duration`             Float32,
    `master`               Bool,
    `timestamp`            DateTime('Europe/Moscow')
) engine = MergeTree
    partition by toYYYYMMDD(timestamp)
    order by (proxy_spec_id, timestamp)
    settings index_granularity = 8192;
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
drop table proxy_spec_logs;
-- +goose StatementEnd
