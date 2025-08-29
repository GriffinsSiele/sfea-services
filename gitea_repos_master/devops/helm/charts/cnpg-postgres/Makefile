currenttime := $(shell date +%s)

configure:
	@helm repo add cnpg https://cloudnative-pg.github.io/charts
	@helm repo update
	
install:
	@helm upgrade -i cnpg --namespace cnpg-system --create-namespace cnpg/cloudnative-pg

uninstall:
	@kubectl delete namespace cnpg-system

install-databases:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(currenttime)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(currenttime)'/g" Chart.yaml
	@helm upgrade -i -n cnpg-system cnpg-databases .

uninstall-databases:
	@helm uninstall -n cnpg-system cnpg-databases
