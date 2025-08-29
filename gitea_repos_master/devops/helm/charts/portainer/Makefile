configure:
	@helm repo add portainer https://portainer.github.io/k8s/
	@helm repo update

install:
	@helm upgrade --create-namespace -i -n portainer portainer portainer/portainer -f values.yaml

install-agent:
	@kubectl apply -f ./portainer-agent-k8s-lb.yaml

uninstall:
	@helm uninstall portainer -n portainer

