#!/bin/sh

# Check if we should run tests
if [ "$1" = "test" ]; then
    export PYTHONPATH="${PYTHONPATH}:."
    echo "Running tests for xiaomi-web-api..."
    pytest -xvs tests/
    exit $?
fi

# Normal startup
HOST="${HOST:=0.0.0.0}"
PORT="${PORT:=8002}"
echo "Starting API on ${HOST}:${PORT}..."
exec uvicorn main:app --host $HOST --port $PORT
