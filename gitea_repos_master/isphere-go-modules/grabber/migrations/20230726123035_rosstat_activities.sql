-- +goose Up
-- +goose StatementBegin
create schema if not exists rosstat;

create table rosstat.activities
(
    code    varchar not null primary key,
    section varchar not null,
    title   varchar not null
);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table rosstat.activities cascade;
drop table rosstat.activities;
-- +goose StatementEnd
