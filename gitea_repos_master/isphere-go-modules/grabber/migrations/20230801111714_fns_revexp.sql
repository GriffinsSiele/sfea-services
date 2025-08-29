-- +goose Up
-- +goose StatementBegin
create table fns.revexp
(
    id         serial         not null primary key,
    inn        varchar(10)    not null,
    income     decimal(20, 2) not null,
    expense    decimal(20, 2) not null,
    updated_at date           not null
);

create index revexp_inn_idx on fns.revexp (inn);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.revexp cascade;

drop table fns.revexp;
-- +goose StatementEnd