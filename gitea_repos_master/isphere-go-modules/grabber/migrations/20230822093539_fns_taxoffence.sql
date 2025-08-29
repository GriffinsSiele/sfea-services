-- +goose Up
-- +goose StatementBegin
create table fns.taxoffence
(
    id         int8           not null primary key,
    inn        varchar        not null,
    sum        decimal(20, 2) not null,
    updated_at date           not null
);

create index taxoffence_inn_idx on fns.taxoffence (inn);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.taxoffence cascade;

drop table fns.taxoffence;
-- +goose StatementEnd