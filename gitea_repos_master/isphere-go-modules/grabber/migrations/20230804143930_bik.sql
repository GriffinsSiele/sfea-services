-- +goose Up
-- +goose StatementBegin
create schema if not exists bik;

create table bik.bik
(
    id                    serial  not null primary key,
    "identity"              varchar not null unique,
    corresponding_account varchar,
    "name"                varchar,
    "alias"               varchar,
    post_index            varchar,
    city                  varchar,
    address               varchar,
    phone                 varchar,
    okato                 varchar,
    okpo                  varchar,
    registration_number   varchar,
    timeframe             varchar,
    created_at            date    not null,
    updated_at            date
);

create index bik_bik_identity on bik.bik (identity);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table bik.bik;
drop table bik.bik;
-- +goose StatementEnd
