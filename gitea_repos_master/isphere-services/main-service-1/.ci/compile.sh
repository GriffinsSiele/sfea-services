#!/bin/sh
cd ..

CURRENT_BRANCH="$(git branch --show-current)"
export CURRENT_BRANCH

docker buildx bake \
    --file .ci/docker/docker-compose.bake.yaml \
    --push \
    --set='*.context=.' \
    --set='*.output=type=registry,registry.insecure=true'
