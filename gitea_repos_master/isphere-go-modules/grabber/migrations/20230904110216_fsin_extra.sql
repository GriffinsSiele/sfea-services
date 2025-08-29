-- +goose Up
-- +goose StatementBegin
alter table fsin.fsin
    add column description      text,
    add column federal_code     varchar,
    add column territorial_code varchar,
    add column external_url     varchar;

create table fsin.images
(
    id         serial    not null primary key,
    fsin_id    serial    not null references fsin.fsin (id) on delete cascade,
    bucket     varchar   not null,
    "key"      varchar   not null,
    mime_type  varchar   not null,
    created_at timestamp not null default current_timestamp
);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
alter table fsin.fsin
    drop column description,
    drop column federal_code,
    drop column territorial_code,
    drop column external_url;

truncate table fsin.images;
drop table fsin.images;
-- +goose StatementEnd
