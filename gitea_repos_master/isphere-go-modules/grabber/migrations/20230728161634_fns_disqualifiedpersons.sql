-- +goose Up
-- +goose StatementBegin
create table fns.disqualifiedpersons
(
    id       serial  not null primary key,
    inn      varchar not null,
    kpp      varchar not null,
    ogrn     varchar not null,
    org_name varchar not null,
    address  varchar
);

create index disqualifiedpersons_inn_idx on fns.disqualifiedpersons (inn);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.disqualifiedpersons;
drop table fns.disqualifiedpersons;
-- +goose StatementEnd
