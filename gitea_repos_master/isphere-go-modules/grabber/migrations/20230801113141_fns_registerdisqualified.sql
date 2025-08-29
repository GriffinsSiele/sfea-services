-- +goose Up
-- +goose StatementBegin
create table fns.registerdisqualified
(
    id                      int8    not null primary key,
    full_name               varchar not null,
    birthday                date    not null,
    birthplace              varchar,
    organization_name       varchar,
    organization_inn        varchar,
    organization_position   varchar,
    reason                  varchar,
    reason_issuer           varchar,
    judge                   varchar,
    judge_position          varchar,
    disqualification_period interval,
    start_at                date    not null,
    end_at                  date
);

create index registerdisqualified_inn_idx on fns.registerdisqualified (organization_inn);
create index registerdisqualified_full_name_birthday on fns.registerdisqualified (full_name, birthday);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
truncate table fns.registerdisqualified cascade;

drop table fns.registerdisqualified;
-- +goose StatementEnd