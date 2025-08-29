configure:
	docker buildx create --config deployments/buildkit.toml

compile:
	docker buildx bake --file deployments/docker-compose.yaml --push --set='*.context=.' --set='*.output=type=registry,registry.insecure=true'

install:
	helm upgrade -i tor-proxy -n tor-proxy --create-namespace -f deployments/helm/values.yaml deployments/helm

uninstall:
	helm uninstall tor-proxy -n tor-proxy
