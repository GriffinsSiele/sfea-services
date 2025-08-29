currenttime := $(shell date +%s)

install:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(currenttime)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(currenttime)'/g" Chart.yaml
	@helm upgrade -i -n 1cloud-storage --create-namespace rabbitmq .

uninstall:
	@helm uninstall rabbitmq -n 1cloud-storage
