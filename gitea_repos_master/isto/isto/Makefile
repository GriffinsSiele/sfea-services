configure:
	docker buildx create --config deployments/buildkit.toml

compile:
	docker buildx bake --file deployments/docker-compose.yaml --push --set='*.context=.' --set='*.output=type=registry,registry.insecure=true'

install:
	helm upgrade -i isto -n isto --create-namespace -f deployments/helm/values.yaml deployments/helm

uninstall:
	helm uninstall isto -n isto
