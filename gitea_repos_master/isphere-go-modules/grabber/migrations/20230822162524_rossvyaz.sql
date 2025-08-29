-- +goose Up
-- +goose StatementBegin
create schema if not exists rossvyaz;

create table rossvyaz.rossvyaz
(
    id               int8       not null primary key,
    abcdef           varchar(3) not null,
    phone_poolstart  varchar(7) not null,
    phone_poolend    varchar(7) not null,
    phone_poolsize   int        not null,
    "operator"       varchar    not null,
    phone_regionname varchar    not null,
    regions          varchar[]  not null,
    regioncode       varchar    not null
);

create unique index rossvyaz_abcdef_phone_poolstart on rossvyaz.rossvyaz (abcdef, phone_poolstart);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table rossvyaz.rossvyaz cascade;

drop table rossvyaz.rossvyaz;
-- +goose StatementEnd