-- +goose Up
-- +goose StatementBegin
create schema if not exists announcements;

create table announcements.announcements
(
    id              serial not null primary key,
    url             varchar,        -- 1
    "category"      varchar,        -- 1
    subcategory     varchar,        -- 1
    region          varchar,        -- 1, 2, 3, 4
    city            varchar,        -- 1, 2, 3, 4
    subway          varchar,        -- 1, 2, 3, 4
    address         varchar,        -- 1, 2, 3, 4
    status          varchar,        -- 1, 2, 3, 4
    company         varchar,        -- 1, 2, 3
    seller          varchar,        -- 1, 2, 3, 4
    contact         varchar,        -- 1, 2, 3, 4
    phone           int8,           -- 1, 2, 3, 4
    "operator"      varchar,        -- 1, 2, 3, 4
    service_regions varchar,        -- 1, 2, 3, 4
    published_at    timestamp,      -- 1, 2, 3, 4
    title           varchar,        -- 1
    parameters      text,           -- 1
    "text"          varchar,        -- 1
    price           decimal(20, 2), -- 1
    latitude        float8,         -- 1, 2
    longitude       float8          -- 1, 2
);

create index announcements_phone_idx on announcements.announcements (phone);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table announcements.announcements;
drop table announcements.announcements;
-- +goose StatementEnd
