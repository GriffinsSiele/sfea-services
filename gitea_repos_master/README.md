# SFEA Services (gitea_repos_master)

A polyglot monorepo containing multiple independently deployable services (Python-first) following DDD-aligned structure. This README maps the services, explains how they interact, and documents setup, env, and run instructions.

## Repo Type

- Monorepo (polyglot): multiple services live in one repository; each has its own dependencies, Dockerfiles, and deployment lifecycle. Shared conventions: DDD 4-layer layout (API/controllers, application/use-cases, domain, infrastructure), standardized responses, middleware-based schema enforcement, optional proxy and Validator IS integration.

## Services
### Xiaomi Aggregator API
- Path: `xiaomi/xiaomi-aggregator-api`
- Purpose: Unified controller to accept a list of inputs, detect types via Validator IS, route phone/email to Xiaomi parsing, and aggregate standardized responses.
- Key endpoints:
  - POST `/api/v1/parse` (batch aggregate)
  - POST `/api/v1/xiaomi/parse`
- Tech: FastAPI, DDD, response schema validation middleware, optional Redis rate limiting; calls `xiaomi-request-api` upstream.

### Xiaomi Request API
- Path: `xiaomi/xiaomi-request-api`
- Purpose: Xiaomi web recovery checker. Solves captcha via external captcha service, uses rotating proxy, hits Xiaomi endpoints, parses results, and returns standardized status.
- Key endpoints:
  - GET `/status`
  - POST `/search`
- Tech: FastAPI, request builders, proxy integration, captcha solver, AES/RSA crypto for Xiaomi payloads, Sentry.

### Huawei Parsing API
- Path: `huawei/huawei-web-api`
- Purpose: Parse input values (phone, email) via unified/batch endpoint using Validator IS for type detection. Returns standardized responses.
- Key endpoints:
  - POST `/api/v1/huawei/parse` (batch aggregate)
  - POST `/api/v1/huawei/phone/parse`
  - POST `/api/v1/huawei/email/parse`
- Tech: FastAPI, DDD 4-layer structure, response schema validation middleware, optional proxy and Validator integration.

### Telegram Search API
- Path: `telegram/telegram-web-api`
- Purpose: Search Telegram users by phone or username; unified controller + aggregator.
- Key endpoints:
  - POST `/api/v1/telegram/search` (unified)
  - POST `/api/v1/aggregate` (batch aggregator)
- Tech: FastAPI, Telethon (live mode), in-memory demo mode, DDD structure, optional Validator and proxy.

## How It Works (End-to-End)

1) Client calls the Aggregator (`xiaomi-aggregator-api`) via POST `/api/v1/parse` with an array of inputs.
2) Aggregator uses the Validator IS to detect each value's type (phone/email/unknown). Fallback regex is used if Validator is disabled/unavailable.
3) For supported types (phone/email), Aggregator routes to service clients (e.g., Xiaomi client).
4) Xiaomi client calls `xiaomi-request-api` POST `/search`.
5) Xiaomi Request API flow:
   - Acquire a proxy from rotating-proxy service
   - Fetch Xiaomi main info and captcha image; send to captcha service for solving
   - Submit captcha result to Xiaomi; on success receive a token
   - Submit the user input with token; parse Xiaomi response into standardized FOUND/NOT_FOUND
6) Request API returns a standardized response; Aggregator composes an AggregatedResponse with `total` and `items` per input.
7) Middleware in Aggregator validates responses conform to schema; non-conforming responses are rejected (500, SCHEMA_INVALID).

## Quickstart

### Prerequisites
- Python 3.11+
- Docker Desktop (for containerized runs)

### Run Huawei Parsing API (Docker)
```powershell
cd huawei/huawei-web-api
Copy-Item .env.example .env
# Optional: add proxy
Add-Content .env "PROXY_URL=http://user:pass@host:port"
Add-Content .env "SENTRY_DSN=YOUR_SENTRY_DSN"
Add-Content .env "REDIS_URL=redis://localhost:6379"
docker compose build
docker compose up -d
# Health
Invoke-RestMethod http://127.0.0.1:8001/health | ConvertTo-Json -Depth 6
```

Example request (PowerShell):
```powershell
$body = (@{ inputs = @("79319999999","test@domain.com") } | ConvertTo-Json)
Invoke-RestMethod -Uri http://127.0.0.1:8001/api/v1/huawei/parse -Method POST -Body $body -ContentType "application/json" | ConvertTo-Json -Depth 8
```

### Run Xiaomi Aggregator + Request API (local)
Xiaomi Request API (in another terminal):
```powershell
cd xiaomi/xiaomi-request-api
pip install pipenv==2022.1.8
pipenv install --skip-lock
$env:PROXY_URL = "http://user:pass@proxy:port"
$env:CAPTCHA_SERVICE_URL = "http://captcha-service:8000"
$env:CAPTCHA_PROVIDER = "<provider>"
uvicorn src.fastapi.main:app --host 0.0.0.0 --port 8000 --log-config=./src/config/logging.yaml
```

Xiaomi Aggregator API:
```powershell
cd xiaomi/xiaomi-aggregator-api
pip install pipenv==2022.1.8
pipenv install --skip-lock
$env:XIAOMI_REQUEST_API_BASE_URL = "http://127.0.0.1:8000"
uvicorn app.main:app --app-dir src --host 0.0.0.0 --port 8005
```

### Run Telegram Search API (local or Docker)
Local (dev):
```powershell
cd telegram/telegram-web-api
python -m pip install -r requirements.txt
python main.py
```
Docker (from service README):
```powershell
cd telegram/telegram-web-api
# Build image
docker build -t telegram-api .
# Run container
docker run -p 8000:8000 --env-file .env telegram-api
```

## Environment Variables (common)
- Validator IS (if used):
  - `VALIDATOR_ENABLED` (bool)
  - `VALIDATOR_BASE_URL` (e.g., https://validator.example.com)
  - `VALIDATOR_API_KEY` (token)
  - `VALIDATOR_TIMEOUT_SECONDS` (int)
  - `VALIDATOR_MAX_RETRIES` (int)
- Proxy (optional):
  - `PROXY_URL` (e.g., `http://user:pass@host:port`)
 - Xiaomi Request API (captcha service):
   - `CAPTCHA_SERVICE_URL` (e.g., http://captcha-service:8000)
   - `CAPTCHA_PROVIDER` (provider id/name configured in captcha service)
 - Xiaomi Aggregator:
   - `XIAOMI_REQUEST_API_BASE_URL` (e.g., http://xiaomi-request-api:8000)

## Response Standardization (batch)
- Huawei batch endpoint returns:
```json
{
  "total": 2,
  "results": [
    {"input":"79319999999","type":"phone","normalized":"+79319999999","data":{"list__phones":["79319999999"]},"result":"Найден","result_code":"FOUND"},
    {"input":"test@domain.com","type":"email","normalized":"test@domain.com","data":{"list__emails":["test@domain.com"]},"result":"Найден","result_code":"FOUND"}
  ]
}
```

## Repository Structure (high level)
```
└─ gitea_repos_master/
   ├─ huawei/
   │  └─ huawei-web-api/         # FastAPI + DDD parsing service
   ├─ xiaomi/
   │  ├─ xiaomi-request-api/     # Upstream Xiaomi checker with proxy+captcha
   │  └─ xiaomi-aggregator-api/  # Unified controller aggregating Xiaomi results
   ├─ telegram/
   │  └─ telegram-web-api/       # FastAPI + DDD Telegram search service
   └─ ...                        # Other projects/services
```

## Troubleshooting
- Docker Desktop must be running (Windows) before `docker compose up`.
- If Huawei container restarts with settings error, ensure `.env` exists and avoid conflicting legacy keys; service uses lowercase settings (e.g., `mode`, `proxy_url` internally via pydantic mapping). The provided `.env.example` is a good baseline.
- Port conflicts: change published port in `docker-compose.yml` or run uvicorn on a free port.
- Proxy usage applies to outbound HTTP calls (e.g., Validator). If you need broader proxying later, integrate via `infrastructure/proxy` in each service.

## Conventions
- DDD 4-layer layout: interface (api/controllers), application (use cases/orchestration), domain (core logic), infrastructure (external clients).
- Typed DTOs and standardized responses.
- Prefer containerized runs for consistent environments.
