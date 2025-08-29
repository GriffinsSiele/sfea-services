current_time := $(shell date +%s)

install:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(current_time)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(current_time)'/g" Chart.yaml
	@helm upgrade -i -n analytics --create-namespace analytics -f values.yaml .

test:
	@helm upgrade -i -n analytics --create-namespace analytics -f values.yaml . --dry-run

uninstall:
	@helm uninstall -n analytics analytics

clean:
	@kubectl delete namespace analytics

install-superset:
	@helm upgrade -i -n analytics superset -f values-superset.yaml superset/superset
	
uninstall-superset:
	@helm uninstall -n analytics superset