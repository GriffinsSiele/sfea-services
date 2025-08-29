-- +goose Up
-- +goose StatementBegin
create schema if not exists fns;

create table fns.taxes
(
    id    serial  not null primary key,
    code  varchar not null unique,
    title varchar not null
);

create index fns_tax_code on fns.taxes (code);

create table fns.debtam
(
    id              uuid        not null primary key,
    inn             varchar(12) not null,
    generation_date date        not null,
    state_date      date        not null
);

create index fns_debtam_inn_idx on fns.debtam (inn);

create table fns.debtam_taxes
(
    id        serial         not null primary key,
    debtam_id uuid           not null references fns.debtam (id) on delete cascade,
    tax_id    int references fns.taxes (id) on delete cascade,
    arrears   decimal(20, 2) not null,
    penalties decimal(20, 2) not null,
    fines     decimal(20, 2) not null,
    "sum"     decimal(20, 2) not null
);

create index fns_debtam_taxes_debtam_id_idx on fns.debtam_taxes(debtam_id);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.debtam cascade;
drop table fns.debtam;

truncate table fns.debtam_taxes cascade;
drop table fns.debtam_taxes;

truncate table fns.taxes cascade;
drop table fns.taxes;
-- +goose StatementEnd