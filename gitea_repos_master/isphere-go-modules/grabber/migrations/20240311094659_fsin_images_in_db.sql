-- +goose Up
-- +goose StatementBegin
alter table fsin.fsin
    add column image           bytea,
    add column image_mime_type varchar;

truncate table fsin.images;
drop table fsin.images;
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
alter table fsin.fsin
    drop column image;
alter table fsin.fsin
    drop column image_mime_type;
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
