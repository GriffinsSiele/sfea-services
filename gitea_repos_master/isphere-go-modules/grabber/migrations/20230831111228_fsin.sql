-- +goose Up
-- +goose StatementBegin
create schema if not exists fsin;

create table fsin.fsin
(
    id         serial  not null primary key,
    surname    varchar not null,
    "name"     varchar not null,
    patronymic varchar,
    birthday   date    not null
);

create index fsin_identity on fsin.fsin (surname, "name", patronymic, birthday);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fsin.fsin;
drop table fsin.fsin;
-- +goose StatementEnd
