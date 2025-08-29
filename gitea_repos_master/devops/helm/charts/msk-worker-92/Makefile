current_time := $(shell date +%s)
                               
install:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(current_time)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(current_time)'/g" Chart.yaml
	@helm upgrade -i -n msk-worker-92 --create-namespace airbyte -f values.yaml .

uninstall:
	@helm uninstall -n msk-worker-92 airbyte

