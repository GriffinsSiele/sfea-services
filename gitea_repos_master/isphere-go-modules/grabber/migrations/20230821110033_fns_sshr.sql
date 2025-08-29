-- +goose Up
-- +goose StatementBegin
create table fns.sshr
(
    id         int8    not null primary key,
    inn        varchar not null,
    count      int     not null,
    updated_at date    not null
);

create index sshr_inn_idx on fns.sshr (inn);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.sshr cascade;

drop table fns.sshr;
-- +goose StatementEnd