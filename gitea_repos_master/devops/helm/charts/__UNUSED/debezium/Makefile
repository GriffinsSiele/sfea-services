current_time := $(shell date +%s)

install:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(current_time)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(current_time)'/g" Chart.yaml
	@helm upgrade -i -n debezium --create-namespace debezium -f values.yaml .

test:
	@helm upgrade -i -n debezium --create-namespace debezium -f values.yaml . --dry-run

uninstall:
	@helm uninstall -n debezium debezium

clean:
	@kubectl delete namespace debezium

