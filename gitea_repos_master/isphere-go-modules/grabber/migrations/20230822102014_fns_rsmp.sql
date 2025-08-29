-- +goose Up
-- +goose StatementBegin
create type rsmp_type as enum (
    'legal_entity',
    'individual_entrepreneur',
    'peasant_farm_head'
    );

create type rsmp_category as enum (
    'micro',
    'small',
    'medium'
    );

create table fns.rsmp
(
    id         int8          not null primary key,
    inn        varchar       not null,
    "type"     rsmp_type     not null,
    "category" rsmp_category not null,
    employees  int,
    created_at date          not null,
    updated_at date          not null
);

create index rsmp_inn_idx on fns.rsmp (inn);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.rsmp cascade;

drop table fns.rsmp;

drop type rsmp_type;
drop type rsmp_category;
-- +goose StatementEnd