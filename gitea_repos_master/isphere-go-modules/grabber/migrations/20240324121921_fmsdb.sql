-- +goose Up
-- +goose StatementBegin
create schema if not exists fmsdb;

create table fmsdb.invalid_passports
(
    series smallint not null,
    number int      not null,
    primary key (series, number)
);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fmsdb.invalid_passports;
drop table fmsdb.invalid_passports;
-- +goose StatementEnd
