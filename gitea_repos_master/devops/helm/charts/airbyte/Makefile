currenttime := $(shell date +%s)

install:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(currenttime)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(currenttime)'/g" Chart.yaml
	@helm upgrade -i -n airbyte --create-namespace airbyte . 

uninstall:
	@helm uninstall airbyte -n airbyte
