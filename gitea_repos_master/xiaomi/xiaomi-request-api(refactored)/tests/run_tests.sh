#!/bin/sh
export PYTHONPATH="${PYTHONPATH}:./src"
echo "Running tests for xiaomi-aggregator-api..."
pytest -xvs tests/
