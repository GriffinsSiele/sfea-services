-- +goose Up
-- +goose StatementBegin
create table fns.massleaders
(
    id         serial  not null primary key,
    inn        varchar not null,
    surname    varchar,
    name       varchar,
    patronymic varchar,
    count      int
);

create index massleaders_inn_idx on fns.massleaders (inn);

-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.massleaders;

drop table fns.massleaders;
-- +goose StatementEnd
