#!/bin/sh
export PYTHONPATH="${PYTHONPATH}:."
echo "Running tests for xiaomi-web-api..."
pytest -xvs tests/
