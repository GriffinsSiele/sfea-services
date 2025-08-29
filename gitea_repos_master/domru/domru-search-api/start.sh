#!/bin/sh

WORKER_COUNT="${GUNICORN_COUNT_WORKERS:=1}"


echo "Starting Gunicorn server..."
exec gunicorn src.fastapi.main:app --workers $WORKER_COUNT --worker-class uvicorn.workers.UvicornWorker --bind 0.0.0.0:80