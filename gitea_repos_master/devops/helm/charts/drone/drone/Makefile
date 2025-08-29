.PHONY: help configure install uninstall values-dist values-install values-fix create status status1 crds download
.DEFAULT_GOAL := help

include .env
$(eval export $(shell sed -ne 's/ *#.*$$//; /./ s/=.*$$// p' .env))

help: ## Show This HELP. (please add .env)
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

configure: ## Add repository ${REPONAME} ${REPOURL} 
	@helm repo add ${REPONAME} ${REPOURL}
	@helm repo update

install:  values-fix ## Install helm chart
	@kubectl create ns ${NS} ||true
	@helm upgrade --install ${RELEASENAME} ${ADD_INSTALL} -f values.yaml -n ${NS}  ${CHARTNAME}

uninstall: ## Uninstall 
	@helm uninstall  ${RELEASENAME} ${ADD_UNINSTALL} -n ${NS} 

values-dist: ## Save default VALUES.YAML
	@helm show values ${CHARTNAME} >values.yaml.dist
	@$(MAKE) --no-print-directory help
values-install: ## Show  installer value
	@helm get  values ${RELEASENAME} -n ${NS}
values-fix: ## Create values.yaml (is not exists)
	@if [ ! -f values.yaml ]; then  touch values.yaml; fi


create: ## Create NEW repository
	@helm create ${RELEASENAME}

status: ## status release.
	@helm  list --selector name=${RELEASENAME}  -n ${NS}

status1: ## Fix status release.(delete selector)
	@helm  list  -n ${NS}

crds:   ## Install CRD's (cdrs_add)
	@kubectl apply -n ${NS} -f crds_add/.

download: ## Download and utar helm chart
	@helm pull --untar ${CHARTNAME}
