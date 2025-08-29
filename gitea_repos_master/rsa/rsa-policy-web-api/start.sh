#!/bin/bash
WORKER_COUNT="${GUNICORN_COUNT_WORKERS:=1}"
echo "Starting server..."
exec uvicorn src.fastapi.main:app --workers $WORKER_COUNT --host 0.0.0.0 --port 80 --log-config=./src/config/logging.yaml