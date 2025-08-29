-- +goose Up
-- +goose StatementBegin
create schema if not exists vk;

create table vk.emails
(
    id    serial  not null primary key,
    vk_id int8    not null,
    email varchar not null
);

create index vk_emails_vk_id_idx on vk.emails (vk_id);
create index vk_emails_email_idx on vk.emails (email);

create table vk.phones
(
    id    serial  not null primary key,
    vk_id int8    not null,
    phone varchar not null
);

create index vk_phones_vk_id_idx on vk.phones (vk_id);
create index vk_phones_phone_idx on vk.phones (phone);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table vk.emails;
drop table vk.emails;

truncate table vk.phones;
drop table vk.phones;
-- +goose StatementEnd
