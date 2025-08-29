-- +goose Up
-- +goose StatementBegin
create table fns.snr
(
    id         int8    not null primary key,
    inn        varchar not null,
    eshn       bool,
    usn        bool,
    ausn       bool,
    srp        bool,
    updated_at date    not null
);

create index snr_inn_idx on fns.snr (inn);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.snr cascade;

drop table fns.snr;
-- +goose StatementEnd