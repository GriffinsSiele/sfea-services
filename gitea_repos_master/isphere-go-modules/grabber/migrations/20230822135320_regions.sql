-- +goose Up
-- +goose StatementBegin
create schema if not exists regions;

create table regions.regions
(
    id    int8      not null primary key,
    code  varchar   not null unique,
    names varchar[] not null
);

create index regions_code_idx on regions.regions (code);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table regions.regions cascade;

drop table regions.regions;
-- +goose StatementEnd