-- +goose Up
-- +goose StatementBegin
create schema if not exists minjust;

create table minjust.foreign_persons
(
    id         serial  not null primary key,
    surname    varchar not null,
    name       varchar not null,
    patronymic varchar,
    birthday   date,
    inn        varchar,
    snils      varchar,
    address    varchar,
    reason     varchar,
    created_at date    not null,
    deleted_at date
);

create index minjust_foreign_persons_fio_birthday_idx on minjust.foreign_persons (surname, name, patronymic, birthday);
create index minjust_foreign_persins_inn on minjust.foreign_persons (inn);

create table minjust.foreign_organizations
(
    id         serial  not null primary key,
    name       varchar,
    inn        varchar not null,
    reg_num    varchar,
    address    varchar,
    reason     varchar,
    created_at date    not null,
    deleted_at date
);

create index minjust_foreign_organizations_inn_idx on minjust.foreign_organizations (inn);

-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table minjust.foreign_persons;

drop table minjust.foreign_persons;

truncate table minjust.foreign_organizations;

drop table minjust.foreign_organizations;
-- +goose StatementEnd
