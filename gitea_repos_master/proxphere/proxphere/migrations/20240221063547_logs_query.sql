-- +goose Up
-- +goose StatementBegin
create table proxy_spec_logs_queue
(
    `proxy_spec_id`        Int32,
    `proxy_spec_group_id`  Int32,
    `request_host`         LowCardinality(String),
    `response_status_code` Int16,
    `error`                Nullable(String),
    `duration`             Float32,
    `master`               Bool,
    `timestamp`            DateTime('Europe/Moscow')
) engine = Kafka
    settings kafka_broker_list = '172.16.199.2:9092',
             kafka_topic_list = 'proxy-spec-logs',
             kafka_group_name = 'proxy',
             kafka_format = 'CSV',
             kafka_num_consumers = 2;
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
drop table proxy_spec_logs_queue;
-- +goose StatementEnd
