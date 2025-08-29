-- +goose Up
-- +goose StatementBegin
create table fns.masaddress
(
    id      serial  not null primary key,
    address varchar not null,
    count   int     not null
);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.masaddress;

drop table fns.masaddress;
-- +goose StatementEnd
