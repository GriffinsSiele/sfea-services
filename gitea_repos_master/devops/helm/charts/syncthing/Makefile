configure:
	@helm repo add k8s-at-home https://k8s-at-home.com/charts/ 
	@helm repo update

install:
	@helm upgrade -i syncthing k8s-at-home/syncthing --version 3.4.1 -n syncthing --create-namespace -f values.yaml

uninstall:
	@kubectl delete namespace syncthing

