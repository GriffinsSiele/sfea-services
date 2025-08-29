#!/bin/sh
cd ..

CURRENT_BRANCH="$(git branch --show-current)"
export CURRENT_BRANCH

if [ $# -gt 0 ]; then
  for arg in "$@"; do
    if [ "$arg" = "--uninstall" ]; then
      helm uninstall \
        main-service-1-"${CURRENT_BRANCH}" \
        -n main-service
      exit 0
    fi
  done

  echo "Unknown option: $1"
  exit 1
fi

helm upgrade -i \
  main-service-1-"${CURRENT_BRANCH}" \
  -n main-service --create-namespace \
  -f .ci/helm/values.yaml \
  --set image.tag="${CURRENT_BRANCH}" \
  --set-string podLabels.date="$(date +%s)" \
  .ci/helm
