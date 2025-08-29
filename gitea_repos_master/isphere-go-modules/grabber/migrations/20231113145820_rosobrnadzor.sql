-- +goose Up
-- +goose StatementBegin
create schema if not exists rosobrnadzor;

create table if not exists rosobrnadzor.registry
(
    id               serial  not null primary key,
    sys_guid         varchar,
    school_guid      varchar,
    status_name      varchar,
    school_name      varchar,
    short_name       varchar,
    inn              varchar not null,
    ogrn             varchar,
    school_type_name varchar,
    law_address      varchar,
    org_name         varchar,
    reg_num          varchar,
    date_lic_doc     date,
    date_end         date
);

create index rosobrnadzor_registry_inn_idx on rosobrnadzor.registry (inn);

create table if not exists rosobrnadzor.supplements
(
    id           serial not null primary key,
    registry_id  int    not null references rosobrnadzor.registry (id) on delete cascade on update cascade,
    license_fk   varchar,
    number       varchar,
    status_name  varchar,
    school_guid  varchar,
    school_name  varchar,
    law_address  varchar,
    org_name     varchar,
    num_lic_doc  varchar,
    date_lic_doc date,
    sys_guid     varchar
);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table rosobrnadzor.supplements cascade;
drop table rosobrnadzor.supplements cascade;
truncate table rosobrnadzor.registry cascade;
drop table rosobrnadzor.registry cascade;
drop schema rosobrnadzor;
-- +goose StatementEnd
