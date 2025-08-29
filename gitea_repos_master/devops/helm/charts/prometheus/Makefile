currenttime := $(shell date +%s)

configure:
	@helm repo add bitnami https://charts.bitnami.com/bitnami
	@helm repo update

install:
	@helm upgrade -i prometheus -n prometheus --create-namespace -f values-kube-prometheus.yaml bitnami/kube-prometheus

configure-loki:
	@helm repo add grafana https://grafana.github.io/helm-charts

install-loki:
	@helm upgrade -i grafana-loki -n prometheus --create-namespace -f values-grafana-loki.yaml grafana/loki

uninstall-loki:
	@helm uninstall grafana-loki -n prometheus

install-promtail:
	@helm upgrade -i grafana-promtail -n prometheus --create-namespace -f values-grafana-promtail.yaml grafana/promtail

uninstall-promtail:
	@helm uninstall grafana-promtail -n prometheus
	
install-grafana-configuration:
	@helm upgrade -i -n prometheus --create-namespace grafana-configuration .

install-grafana:
	@sed -E -i '' -e "s/appVersion.*/appVersion:\ '$(currenttime)'/g" Chart.yaml
	@sed -E -i '' -e "s/version.*/version:\ '$(currenttime)'/g" Chart.yaml
	@helm upgrade -i grafana-configuration -n prometheus --create-namespace .
	@kubectl apply -f ./templates/grafana.yaml
	@helm upgrade -i grafana -n prometheus --create-namespace -f values-grafana.yaml bitnami/grafana

uninstall-grafana:
	@helm uninstall grafana -n prometheus
	@helm uninstall grafana-configuration -n prometheus
