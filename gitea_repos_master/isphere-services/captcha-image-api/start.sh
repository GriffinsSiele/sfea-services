#!/bin/sh

WORKER_COUNT="${GUNICORN_COUNT_WORKERS:=1}"

echo "Running database preload..."
python3 runners/db_preload.py

echo "Starting Gunicorn server..."
exec gunicorn src.main:app --workers $WORKER_COUNT --worker-class uvicorn.workers.UvicornWorker --bind 0.0.0.0:80
