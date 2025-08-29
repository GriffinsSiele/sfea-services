currenttime := $(shell date +%s)

configure:
	@helm repo add mysql-operator https://mysql.github.io/mysql-operator/
	@helm repo update
	
install:
	@helm upgrade -i mysql --namespace mysql --create-namespace -f values.yaml mysql-operator/mysql-operator

uninstall: # Для MYSQL оператора нельзя так делать!!!!! он так не удалит: finalizer помешает...........
	# @kubectl delete namespace mysql

install-databases:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(currenttime)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(currenttime)'/g" Chart.yaml
	@helm upgrade -i -n mysql -f values-databases.yaml mysql-databases .

uninstall-databases:
	@helm uninstall -n mysql mysql-databases
