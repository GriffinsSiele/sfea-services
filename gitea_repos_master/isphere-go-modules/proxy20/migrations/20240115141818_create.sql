-- +goose Up
-- +goose StatementBegin
create table proxy_specs (
    id serial not null primary key,
    server varchar not null,
    port int not null,
    login varchar,
    password varchar,
    proxygroup int,
    country varchar not null,
    enabled boolean not null default false
);

create index proxy_specs_proxygroup_idx on proxy_specs (proxygroup);

create index proxy_specs_country_idx on proxy_specs (country);

create index proxy_specs_enabled_idx on proxy_specs (enabled);

-- +goose StatementEnd
-- +goose Down
-- +goose StatementBegin
drop table proxy_specs;

-- +goose StatementEnd