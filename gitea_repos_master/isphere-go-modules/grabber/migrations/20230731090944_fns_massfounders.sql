-- +goose Up
-- +goose StatementBegin
create table fns.massfounders
(
    id         serial  not null primary key,
    inn        varchar not null,
    surname    varchar,
    name       varchar,
    patronymic varchar,
    count      int
);

create index massfounders_inn_idx on fns.massfounders (inn);

-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.massfounders;

drop table fns.massfounders;
-- +goose StatementEnd
