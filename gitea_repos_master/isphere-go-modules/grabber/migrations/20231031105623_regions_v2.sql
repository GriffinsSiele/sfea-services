-- +goose Up
-- +goose StatementBegin
create schema if not exists regions;

create table regions.regions_v2_districts
(
    id     int8    not null primary key,
    "name" varchar not null unique
);

create table regions.regions_v2
(
    id          int8       not null primary key,
    district_id int8 references regions.regions_v2_districts (id) on delete cascade,
    code        varchar(2) unique not null,
    "name"      varchar
);

create index regions_v2_code_idx on regions.regions_v2 (code);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table regions.regions_v2 cascade;
truncate table regions.regions_v2_districts cascade;

drop table regions.regions_v2;
drop table regions.regions_v2_districts;
-- +goose StatementEnd
