#!/bin/sh

# Check if we should run tests
if [ "$1" = "test" ]; then
    export PYTHONPATH="${PYTHONPATH}:./src"
    echo "Running tests for xiaomi-aggregator-api..."
    pytest -xvs tests/
    exit $?
fi

# Normal startup
WORKER_COUNT="${GUNICORN_COUNT_WORKERS:=1}"
HOST="${HOST:=0.0.0.0}"
PORT="${PORT:=8005}"
export PYTHONPATH="${PYTHONPATH}:./src"
echo "Starting API on ${HOST}:${PORT} with ${WORKER_COUNT} workers..."
exec uvicorn app.main:app --app-dir src --workers $WORKER_COUNT --host $HOST --port $PORT
