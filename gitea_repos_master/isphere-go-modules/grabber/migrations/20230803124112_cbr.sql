-- +goose Up
-- +goose StatementBegin
create schema if not exists cbr;

create table cbr.titles
(
    id    serial  not null primary key,
    title varchar not null unique
);

create table cbr.reasons
(
    id    serial  not null primary key,
    title varchar not null unique
);

create table cbr.cbr
(
    id        serial  not null primary key,
    title_id  int     not null references cbr.titles (id),
    reason_id int     not null references cbr.reasons (id),
    inn       varchar not null
);

create index cbr_cbr_inn_idx on cbr.cbr (inn);
create index cbr_cbr_title_id_idx on cbr.cbr (title_id);
create index cbr_cbr_reason_id_idx on cbr.cbr (reason_id);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table cbr.titles;
drop table cbr.titles;

truncate table cbr.reasons;
drop table cbr.reasons;

truncate table cbr.cbr;
drop table cbr.cbr;
-- +goose StatementEnd
