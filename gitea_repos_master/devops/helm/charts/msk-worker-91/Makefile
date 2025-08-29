current_time := $(shell date +%s)
                               
install:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(current_time)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(current_time)'/g" Chart.yaml
	@helm upgrade -i -n msk-worker-91 --create-namespace clickhouse .

uninstall:
	@helm uninstall -n msk-worker-91 clickhouse
