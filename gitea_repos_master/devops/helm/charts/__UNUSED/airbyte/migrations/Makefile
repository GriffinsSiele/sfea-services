build:
	@go build -o goose-custom .

up:
	@./goose-custom clickhouse 'tcp://analytics:analytics@clickhouse-tcp.analytics.svc.cluster.local:9000' up-by-one

up-nonstop:
	@./goose-custom clickhouse 'tcp://analytics:analytics@clickhouse-tcp.analytics.svc.cluster.local:9000' up

down:
	@./goose-custom clickhouse 'tcp://analytics:analytics@clickhouse-tcp.analytics.svc.cluster.local:9000' down
