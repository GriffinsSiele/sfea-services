-- +goose Up
-- +goose StatementBegin
create table fns.paytax
(
    id              uuid        not null primary key,
    inn             varchar(12) not null,
    generation_date date        not null,
    state_date      date        not null
);

create index fns_paytax_inn_idx on fns.paytax (inn);

create table fns.paytax_taxes
(
    id        serial         not null primary key,
    paytax_id uuid           not null references fns.paytax (id) on delete cascade,
    tax_id    int references fns.taxes (id) on delete cascade,
    "sum"     decimal(20, 2) not null
);

create index fns_paytax_taxes_paytax_id_idx on fns.paytax_taxes (paytax_id);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.paytax_taxes cascade;
drop table fns.paytax_taxes;

truncate table fns.paytax cascade;
drop table fns.paytax;
-- +goose StatementEnd
