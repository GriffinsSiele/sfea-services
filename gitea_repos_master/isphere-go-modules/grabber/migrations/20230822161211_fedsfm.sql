-- +goose Up
-- +goose StatementBegin
create schema if not exists fedsfm;

create table fedsfm.terrorists
(
    id         int8    not null primary key,
    surname    varchar not null,
    "name"     varchar not null,
    patronymic varchar,
    birthday   date,
    birthplace varchar
);

create index fedsfm_surname_name_patronymic_birthday_idx on fedsfm.terrorists (surname, "name", patronymic, birthday);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fedsfm.terrorists cascade;

drop table fedsfm.terrorists;
-- +goose StatementEnd