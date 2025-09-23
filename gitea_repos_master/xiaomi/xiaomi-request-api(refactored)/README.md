# xiaomi-request-api

Unified FastAPI controller that accepts a list of inputs (phone/email), detects types via the Validator IS, routes supported types to the Xiaomi parsing service, and returns a standardized aggregated response. Production-ready with schema validation middleware and Docker targets.

## Features
- Unified endpoint to process multiple inputs at once.
- Per-service endpoint for Xiaomi-only parsing.
- Validator IS client with retries and regex fallback.
- Response schema validation middleware + request ID header.
- Optional Redis-backed rate limiting.
- Docker images (dev/prod) and simple start script.

## Endpoints

### POST /api/v1/parse
- Body:
```json
{ "values": ["79319999999", "user@example.com"] }
```
- Response (example):
```json
{
  "success": true,
  "total": 2,
  "items": [
    {
      "input": "79319999999",
      "type": "phone",
      "service": "xiaomi",
      "success": true,
      "found": true,
      "data": { "records": [{ "result": "Найден", "result_code": "FOUND" }] }
    },
    {
      "input": "user@example.com",
      "type": "email",
      "service": "xiaomi",
      "success": false,
      "found": null,
      "error": "…",
      "error_code": "XIAOMI_5xx"
    }
  ]
}
```

### POST /api/v1/xiaomi/parse
- Body:
```json
{ "value": "user@example.com" }
```
- Response matches `ParseResponse` in `src/app/api/v1/schemas.py`.

### POST /api/v1/aggregate
- Body (supports either key):
```json
{ "query": ["79319999999", "user@example.com"] }
```
or
```json
{ "values": ["79319999999", "user@example.com"] }
```
- Response: Array of result items in the required envelope
```json
[
  {
    "headers": { "sender": "tw.tools.validator" },
    "body": { "request_data": "79319999999", "type": "phone", "clean_data": "79319999999" },
    "extra": { "request_data": "79319999999", "clean_data": "79319999999" }
  },
  {
    "headers": { "sender": "xiaomi" },
    "body": { "request_data": "79319999999", "type": "phone", "clean_data": "79319999999" },
    "extra": { "records": [{ "result": "Найден", "result_code": "FOUND" }] }
  }
]
```

## Configuration

Use `.env` (see `env.example` for a template):
- XIAOMI_REQUEST_API_BASE_URL: Base URL of upstream `xiaomi-request-api` (e.g. `http://localhost:8000`)
- VALIDATOR_ENABLED, VALIDATOR_BASE_URL, VALIDATOR_API_KEY: Toggle and configure Validator IS (optional)
- VALIDATOR_TIMEOUT_SECONDS, VALIDATOR_MAX_RETRIES: Validator client tuning
- PROXY_URL: Optional corporate proxy URL for outbound requests
- REDIS_URL, RATE_LIMIT_WINDOW_SECONDS, RATE_LIMIT_MAX_REQUESTS: Optional rate limiting
- PORT: Service port (default 8005)

## Quickstart (local)

- Ensure `xiaomi-request-api` is running (default `http://localhost:8000`).
- Run:
```bash
uvicorn app.main:app --app-dir src --host 0.0.0.0 --port 8005
```

## Docker

- Dev (autoreload):
```bash
docker build -f Dockerfile.dev -t xiaomi-aggregator-api .
docker run -p 8005:8005 --env-file .env xiaomi-aggregator-api
```

- Prod:
```bash
docker build -f Dockerfile.prod -t xiaomi-aggregator-api .
docker run -p 8005:8005 --env-file .env xiaomi-aggregator-api
```

## API Usage Examples

- Batch parse:
```bash
curl -s http://localhost:8005/api/v1/parse \
  -H "Content-Type: application/json" \
  -d '{"values": ["79319999999", "user@example.com"]}'
```

- Xiaomi-only parse:
```bash
curl -s http://localhost:8005/api/v1/xiaomi/parse \
  -H "Content-Type: application/json" \
  -d '{"value": "user@example.com"}'
```

## Architecture (DDD-ish)

- API layer: `src/app/api/v1` (controllers, schemas)
- Core config: `src/app/core/settings.py`
- Middleware: `src/app/middleware.py` (request ID, schema validation, optional rate limit)
- Infrastructure:
  - Validator client: `src/app/infrastructure/validator/client.py`
  - Xiaomi upstream client: `src/app/infrastructure/services/xiaomi_client.py`

## Operations

- Health: use a basic request to any endpoint; responses include enforced schema.
- Observability: request ID via `X-Request-ID` header; set `SENTRY_DSN` if adding Sentry.
- Rate limiting: enable via `REDIS_URL` with window and max requests.

## Notes

- Xiaomi is wired now; add more services by implementing a client and extending routing.
- Responses are validated by middleware; non-conforming bodies return 500 with `SCHEMA_INVALID`.

