# Huawei Parsing API

A minimal DDD-based FastAPI service for parsing input values (phone, email) with a unified controller, optional Validator IS integration, and standardized responses.

Run locally (Windows PowerShell):

```bash
python -m venv .venv
./.venv/Scripts/Activate.ps1
python -m pip install -r requirements.txt
python -m uvicorn main:app --host 127.0.0.1 --port 8001
```

Scope of Work alignment:
- Python-only, core parsing logic isolated in `domain/services`.
- 4-layer DDD: interface (api/controllers), application (controller orchestration), domain (parsers), infrastructure (validator/proxy stubs).
- Unified endpoint `/api/v1/huawei/parse` accepts array of strings and auto-detects types via Validator IS; per-type endpoints under `/api/v1/huawei/{phone|email}/parse`.
- Output standardization: aggregator returns `{ total, results: ParseItem[] }`.

Docker (requires Docker Desktop running):
```powershell
Copy-Item .env.example .env
# Optional proxy (example)
Add-Content .env "PROXY_URL=http://user:pass@host:port"
# Optional Sentry and Redis rate limiting
Add-Content .env "SENTRY_DSN=YOUR_SENTRY_DSN"
Add-Content .env "REDIS_URL=redis://localhost:6379"
Add-Content .env "RATE_LIMIT_WINDOW_SECONDS=3600"
Add-Content .env "RATE_LIMIT_MAX_REQUESTS=100"
docker compose build
docker compose up -d
Invoke-RestMethod http://127.0.0.1:8001/health | ConvertTo-Json -Depth 6
```

Example request/response:
```http
POST /api/v1/huawei/parse
{
  "inputs": ["79319999999", "test@domain.com", "unknown123"]
}

200 OK
{
  "total": 3,
  "results": [
    {"input":"79319999999","type":"phone","normalized":"+79319999999","data":{"list__phones":["79319999999"]},"result":"Найден","result_code":"FOUND"},
    {"input":"test@domain.com","type":"email","normalized":"test@domain.com","data":{"list__emails":["test@domain.com"]},"result":"Найден","result_code":"FOUND"},
    {"input":"unknown123","type":"unknown","normalized":null,"data":null,"result":"Не найден","result_code":"NOT_FOUND","notes":["Unsupported input type"]}
  ]
}
```

Validation & observability (non-security):
- Response schema validation enforced by middleware for unified and per-service endpoints.
- Each response carries `X-Request-ID` header for traceability.

Testing:
- Run unit tests: `pytest -q` (requires adding pytest to your tooling if desired).

Final Deliverables coverage:
1) Python-based services in DDD structure: provided under `api/`, `controllers/`, `domain/`, `infrastructure/`, `core/`.
2) Unified controller with single endpoint for routing: `POST /api/v1/huawei/parse` with Validator-based detection.
3) Dockerized environments: `Dockerfile` (dev), `Dockerfile.prod` (prod), `docker-compose.yml`.
4) Documentation:
   - Service APIs:
     - `POST /api/v1/huawei/parse` body: `{ "inputs": ["79319999999", "user@mail.com"] }` → `{ "total": N, "results": [ParseItem...] }`
     - `POST /api/v1/huawei/phone/parse` body: `{ "value": "79319999999" }` → `{ "item": ParseItem }`
     - `POST /api/v1/huawei/email/parse` body: `{ "value": "user@mail.com" }` → `{ "item": ParseItem }`
   - "Validator" IS protocol:
     - Env: `VALIDATOR_ENABLED`, `VALIDATOR_BASE_URL`, `VALIDATOR_API_KEY`, `VALIDATOR_TIMEOUT_SECONDS`, `VALIDATOR_MAX_RETRIES`.
     - Request: `POST {VALIDATOR_BASE_URL}/detect` with `{ "value": "..." }`.
     - Expected response: `{ "type": "phone|email|unknown", "confidence": number }`.
     - Client: `infrastructure/validator/client.py` with retry/backoff + circuit breaker and local fallback.
   - Proxy/auth setup:
     - Env: `PROXY_URL` (optional).
     - Client stub: `infrastructure/proxy/rotating_proxy.py` → returns `{ "url": PROXY_URL }` for integration.

Quick endpoint examples (PowerShell):
```powershell
# Aggregator
$body = (@{ inputs = @("79319999999","test@domain.com") } | ConvertTo-Json)
Invoke-RestMethod -Uri http://127.0.0.1:8001/api/v1/huawei/parse -Method POST -Body $body -ContentType "application/json" | ConvertTo-Json -Depth 8

# Phone-only
$body = (@{ value = "79319999999" } | ConvertTo-Json)
Invoke-RestMethod -Uri http://127.0.0.1:8001/api/v1/huawei/phone/parse -Method POST -Body $body -ContentType "application/json" | ConvertTo-Json -Depth 8

# Email-only
$body = (@{ value = "test@domain.com" } | ConvertTo-Json)
Invoke-RestMethod -Uri http://127.0.0.1:8001/api/v1/huawei/email/parse -Method POST -Body $body -ContentType "application/json" | ConvertTo-Json -Depth 8
```

Troubleshooting:
- If container restarts with settings error, ensure `.env` contains `MODE=development` or remove it; the service reads `mode` (lowercase) via Pydantic and ignores unknown env keys.
- Ensure Docker Desktop is running before `docker compose up`.


