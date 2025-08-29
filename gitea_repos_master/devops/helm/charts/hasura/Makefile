currenttime := $(shell date +%s)

configure:
	@helm repo add hasura-extra https://hasura-extra.github.io/hasura-extra
	@helm repo update

install:
	@helm upgrade -i -n hasura --create-namespace -f values.yaml hasura hasura-extra/hasura

uninstall:
	@helm uninstall -n hasura hasura
