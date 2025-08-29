-- +goose Up
-- +goose StatementBegin
create table proxy_specs_logs (
    id serial not null primary key,
    proxy_spec_id int not null references proxy_specs (id) on delete cascade,
    host varchar not null,
    status_code int not null,
    duration interval not null,
    message varchar,
    created_at timestamp not null default now()
);

create index proxy_specs_logs_proxy_spec_id_idx on proxy_specs_logs (proxy_spec_id);

create index proxy_specs_logs_created_at_idx on proxy_specs_logs (created_at);

-- +goose StatementEnd
-- +goose Down
-- +goose StatementBegin
drop table proxy_specs_logs;

-- +goose StatementEnd