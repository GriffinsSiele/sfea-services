-- +goose Up
-- +goose StatementBegin
create index rosobrnadzor_supplements_registry_id_idx on rosobrnadzor.supplements (registry_id);
-- +goose StatementEnd

-- +goose Down
-- +goose StatementBegin
drop index rosobrnadzor_supplements_registry_id_idx
-- +goose StatementEnd
