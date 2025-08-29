currenttime := $(shell date +%s)

configure:
	@helm repo add percona https://percona.github.io/percona-helm-charts/
	@helm repo update

install:
	@helm upgrade -i -n mongodb --create-namespace -f values.yaml mongodb-operator percona/psmdb-operator

uninstall:
	@helm uninstall -n mongodb mongodb-operator

install-databases:
	@helm install sessions percona/psmdb-db --namespace mongodb -f values-sessions.yaml

uninstall-databases:
	@helm uninstall -n mongodb sessions
