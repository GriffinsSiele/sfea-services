-- +goose Up
-- +goose StatementBegin
-- @formatter:off
create table isphere_stats.RequestSource (
    created_date Date32,
    request_id Int32,
    source_name String,
    checktype String,
    start_param Nullable(String),
    process_time Nullable(Float32),
    res_code Int32,
    client_id Int32,
    client_code String,
    client_name String,
    user_id Int32,
    user_login String,
    user_default_price Nullable(Float32),
    user_source_price Nullable(Float32),
    master_user_id Int32,
    master_user_login String,
    master_user_default_price Nullable(Float32),
    master_user_source_price Nullable(Float32),
    ip Nullable(String)
)
engine = MergeTree()
partition by toYYYYMM(created_date)
order by (created_date, request_id, source_name, checktype, res_code, client_id, user_id, master_user_id);
-- @formatter:on
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
drop table if exists isphere_stats.RequestSource;
-- +goose StatementEnd
