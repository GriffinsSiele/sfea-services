# Xiaomi Recovery API

A DDD-based FastAPI service for parsing input values (phone, email). Provides a unified batch endpoint that auto-detects types via Validator IS and independent per-type endpoints, with standardized responses.

Run locally (Windows PowerShell):

```powershell
python -m venv .venv
./.venv/Scripts/Activate.ps1
python -m pip install -r requirements.txt
python -m uvicorn main:app --host 127.0.0.1 --port 8002
```

Docker (requires Docker Desktop running):
```powershell
# Optional proxy/observability/rate limiting
if (!(Test-Path .env)) { Set-Content .env "MODE=development`nPORT=8002" }
Add-Content .env "PROXY_URL=http://user:pass@host:port"
Add-Content .env "SENTRY_DSN=YOUR_SENTRY_DSN"
Add-Content .env "REDIS_URL=redis://localhost:6379"
Add-Content .env "RATE_LIMIT_WINDOW_SECONDS=3600"
Add-Content .env "RATE_LIMIT_MAX_REQUESTS=100"

docker compose build
docker compose up -d
Invoke-RestMethod http://127.0.0.1:8002/health | ConvertTo-Json -Depth 6
```

Endpoints
- POST `/api/v1/xiaomi/parse` (batch; auto-detect via Validator)
- POST `/api/v1/xiaomi/phone/parse`
- POST `/api/v1/xiaomi/email/parse`

Example requests (PowerShell):
```powershell
# Batch
$body = (@{ inputs = @("79319999999","test@domain.com","unknown123") } | ConvertTo-Json)
Invoke-RestMethod -Uri http://127.0.0.1:8002/api/v1/xiaomi/parse -Method POST -Body $body -ContentType "application/json" | ConvertTo-Json -Depth 8

# Phone-only
$body = (@{ value = "79319999999" } | ConvertTo-Json)
Invoke-RestMethod -Uri http://127.0.0.1:8002/api/v1/xiaomi/phone/parse -Method POST -Body $body -ContentType "application/json" | ConvertTo-Json -Depth 8

# Email-only
$body = (@{ value = "test@domain.com" } | ConvertTo-Json)
Invoke-RestMethod -Uri http://127.0.0.1:8002/api/v1/xiaomi/email/parse -Method POST -Body $body -ContentType "application/json" | ConvertTo-Json -Depth 8
```

Standardized batch response
```json
{
  "total": 3,
  "results": [
    {"input":"79319999999","type":"phone","normalized":"+79319999999","data":{"list__phones":["79319999999"]},"result":"Найден","result_code":"FOUND"},
    {"input":"test@domain.com","type":"email","normalized":"test@domain.com","data":{"list__emails":["test@domain.com"]},"result":"Найден","result_code":"FOUND"},
    {"input":"unknown123","type":"unknown","normalized":null,"data":null,"result":"Не найден","result_code":"NOT_FOUND","notes":["Unsupported input type"]}
  ]
}
```

Environment
- Validator IS (optional): `VALIDATOR_ENABLED`, `VALIDATOR_BASE_URL`, `VALIDATOR_API_KEY`, `VALIDATOR_TIMEOUT_SECONDS`, `VALIDATOR_MAX_RETRIES`
- Proxy (optional): `PROXY_URL`
- Observability (optional): `SENTRY_DSN`
- Rate limiting (optional): `REDIS_URL`, `RATE_LIMIT_WINDOW_SECONDS`, `RATE_LIMIT_MAX_REQUESTS`

Troubleshooting
- Ensure Docker Desktop is running before `docker compose up`.
- If container restarts due to settings, ensure `.env` exists; service uses lowercase settings via Pydantic and ignores unknown keys.

