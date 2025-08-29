# Telegram Search API

A modern, DDD-compliant API service for searching Telegram users by phone number or username.

## 🏗️ Architecture

This service follows **Domain-Driven Design (DDD)** principles with a clean, layered architecture:

```
src/
├── domain/           # Domain layer (entities, value objects, repositories, services)
├── application/      # Application layer (use cases, DTOs)
├── infrastructure/   # Infrastructure layer (external services, databases)
└── interface/        # Interface layer (controllers, middleware)
```

### Key Features

- **Unified API Endpoint**: Single `/search` endpoint that automatically routes based on data type
  and a new batch aggregator endpoint `/api/v1/aggregate` that detects types and routes per item
- **Data Type Detection**: Automatically detects phone numbers vs usernames
- **Standardized Responses**: Consistent JSON response format
- **Rate Limiting**: Built-in rate limiting and request validation
- **Error Handling**: Comprehensive error handling with standardized error responses
- **Health Monitoring**: Built-in health checks and monitoring

## 🚀 Quick Start

### Prerequisites

- Python 3.11+
- MongoDB
- Redis/KeyDB
- Telegram API credentials

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd telegram-web-api
   ```

2. **Install dependencies**
   ```bash
   pip install -r requirements.txt
   # or
   pipenv install
   ```

3. **Set up environment variables**
   ```bash
   cp .env.example .env
   # Edit .env with your configuration
   ```

4. **Run the service**
   ```bash
   python main.py
   # or
   uvicorn main:app --reload
   ```

### Docker

```bash
# Build image
docker build -t telegram-api .

# Run container
docker run -p 8000:8000 --env-file .env telegram-api
```

#### Production build (hardened)

```bash
docker build -f Dockerfile.prod -t telegram-api:prod .
docker run -p 8000:8000 --env-file .env --read-only --tmpfs /tmp telegram-api:prod
```

## 📡 API Usage

### Search Endpoint

**POST** `/api/v1/telegram/search`

Search for Telegram users by phone number or username, or use a unified `value` field (auto-detected).

#### Request Body

```json
{
  "phone": "79319999999"
}
```

**OR**

```json
{
  "username": "testuser"
}
```

**OR (Unified value)**

```json
{
  "value": "79319999999"
}
```

#### Response Format

```json
{
  "success": true,
  "data": [
    {
      "id": 123456789,
      "username": "testuser",
      "first_name": "Test",
      "last_name": "User",
      "phone": "79319999999",
      "is_bot": false,
      "is_verified": false,
      "is_restricted": false,
      "is_scam": false,
      "is_fake": false,
      "access_hash": 12345678901234567890,
      "photo": null,
      "status": "online",
      "created_at": "2024-01-01T00:00:00",
      "updated_at": "2024-01-01T00:00:00"
    }
  ],
  "errors": [],
  "metadata": {
    "search_type": "phone",
    "query": "79319999999",
    "results_count": 1,
    "session_id": "session_123"
  }
}
```

### Health Check

**GET** `/api/v1/telegram/health`

```json
{
  "status": "healthy",
  "service": "telegram-api",
  "version": "1.0.0",
  "environment": "development"
}
```

### Aggregator Endpoint (Unified Controller)

**POST** `/api/v1/aggregate`

Request (PowerShell):

```powershell
$body = @{ inputs = @('79319999999','testuser') } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/aggregate' -Method POST -ContentType 'application/json' -Body $body | ConvertTo-Json -Depth 6
```

Response (example):

```json
{
  "success": true,
  "results": [
    { "input": "79319999999", "type": "phone", "success": true, "data": [{ "id": 123456789, "username": "testuser" }] },
    { "input": "testuser", "type": "username", "success": true, "data": { "id": 987654321, "username": "testuser" } }
  ],
  "errors": null,
  "metadata": null
}
```

### Validator Integration

When `VALIDATOR_ENABLED=true`, the service verifies the input type using the Validator IS before routing. If the detected type doesn't match the provided field, it returns `VALIDATOR_TYPE_MISMATCH`. Unified `value` requests are detected and routed automatically.

## ⚙️ Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `MODE` | Environment mode | `development` |
| `PORT` | Service port | `8000` |
| `MONGO_URL` | MongoDB connection string | `mongodb://localhost:27017` |
| `MONGO_DB` | MongoDB database name | `telegram_api` |
| `KEYDB_URL` | Redis/KeyDB connection string | `redis://localhost:6379` |
| `TELEGRAM_API_ID` | Telegram API ID | Required |
| `TELEGRAM_API_HASH` | Telegram API Hash | Required |
| `MAX_REQUESTS_PER_HOUR` | Rate limit per hour | `100` |

### Example .env file

```env
MODE=development
PORT=8000
MONGO_URL=mongodb://localhost:27017
MONGO_DB=telegram_api
KEYDB_URL=redis://localhost:6379
TELEGRAM_API_ID=your_api_id
TELEGRAM_API_HASH=your_api_hash
SENTRY_URL=your_sentry_url
```

## 🧪 Testing

### Run Tests

```bash
# Run all tests
pytest

# Run with coverage
pytest --cov=src

# Run specific test file
pytest tests/test_telegram.py
```

### Test Structure

```
tests/
├── unit/           # Unit tests
├── integration/    # Integration tests
└── fixtures/       # Test fixtures and data
```

## 🔧 Development

### Code Quality

```bash
# Format code
black src/
isort src/

# Type checking
mypy src/

# Lint code
flake8 src/
```

### Project Structure

```
telegram-web-api/
├── src/
│   ├── domain/           # Domain layer
│   │   ├── entities/     # Business entities
│   │   ├── value_objects/# Value objects
│   │   ├── repositories/ # Repository interfaces
│   │   └── services/     # Domain services
│   ├── application/      # Application layer
│   │   ├── use_cases/    # Use cases
│   │   └── dto/          # Data transfer objects
│   ├── infrastructure/   # Infrastructure layer
│   │   └── telegram/     # Telegram client adapters
│   └── interface/        # Interface layer
│       ├── controllers/  # API controllers
│       └── middleware/   # Middleware components
├── tests/                # Test files
├── main.py              # FastAPI application
├── requirements.txt     # Python dependencies
├── Dockerfile          # Docker configuration
└── README.md           # This file
```

## 🚨 Error Handling

The API provides standardized error responses with the following structure:

```json
{
  "success": false,
  "error": "Error message",
  "error_code": "ERROR_CODE",
  "details": {
    "additional": "information"
  },
  "timestamp": "2024-01-01T00:00:00Z"
}
```

### Common Error Codes

- `VALIDATION_ERROR` - Request validation failed
- `RATE_LIMIT_EXCEEDED` - Rate limit exceeded
- `TELEGRAM_RATE_LIMIT` - Telegram API rate limit
- `INVALID_PHONE` - Invalid phone number format
- `INVALID_USERNAME` - Invalid username format
- `USERNAME_NOT_FOUND` - Username not found
- `CLIENT_NOT_CONNECTED` - Telegram client not connected

## 📊 Monitoring

### Health Checks

- **Health endpoint**: `/api/v1/telegram/health`
- **Docker health check**: Built-in health check with 30s intervals
- **Sentry integration**: Error tracking and monitoring

### Logging

Structured logging with configurable levels:

```python
import logging
logger = logging.getLogger(__name__)
logger.info("Search request received", extra={"phone": phone, "session_id": session_id})
```

## 🔒 Security

- **Input validation**: Comprehensive request validation
- **Rate limiting**: Built-in rate limiting per client
- **Error sanitization**: Errors don't expose sensitive information
- **CORS configuration**: Configurable CORS settings

## 🚀 Deployment

### Kubernetes

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: telegram-api
spec:
  replicas: 3
  selector:
    matchLabels:
      app: telegram-api
  template:
    metadata:
      labels:
        app: telegram-api
    spec:
      containers:
      - name: telegram-api
        image: telegram-api:latest
        ports:
        - containerPort: 8000
        env:
        - name: MODE
          value: "production"
        - name: MONGO_URL
          valueFrom:
            secretKeyRef:
              name: telegram-secrets
              key: mongo-url
```

### Docker Compose

```yaml
version: '3.8'
services:
  telegram-api:
    build: .
    ports:
      - "8000:8000"
    environment:
      - MODE=development
    depends_on:
      - mongodb
      - redis
  
  mongodb:
    image: mongo:6.0
    ports:
      - "27017:27017"
  
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Run the test suite
6. Submit a pull request

## 📦 Live Endpoint Outputs

### GET /
```json
{"service":"Telegram Search API","version":"1.0.0","description":"Unified API for searching Telegram users by phone or username","status":"running","environment":"development","endpoints":{"search":"/api/v1/telegram/search","health":"/api/v1/telegram/health","docs":"/docs"}}
```

### GET /health
```json
{"status":"healthy","service":"telegram-api","version":"1.0.0","environment":"development"}
```

### GET /api/v1/telegram/health
```json
{"status":"healthy","service":"telegram-api","version":"1.0.0","timestamp":"2025-08-26T12:07:57.712572"}
```

### POST /api/v1/telegram/search

PowerShell examples (avoid backslashes in JSON):
```powershell
$body = @{ phone = '79319999999' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/telegram/search' -Method POST -ContentType 'application/json' -Body $body

$body = @{ username = 'testuser' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/telegram/search' -Method POST -ContentType 'application/json' -Body $body
```

Expected success (phone):
```json
{
  "success": true,
  "data": [
    {
      "id": 123456789,
      "username": "testuser",
      "first_name": "Test",
      "last_name": "User",
      "phone": "79319999999",
      "is_bot": false,
      "is_verified": false,
      "is_restricted": false,
      "is_scam": false,
      "is_fake": false,
      "access_hash": 12345678901234567890,
      "photo": null,
      "status": "online",
      "created_at": "2025-08-26T09:12:20.556782",
      "updated_at": "2025-08-26T09:12:20.556791"
    }
  ],
  "errors": [],
  "metadata": {
    "search_type": "phone",
    "query": "79319999999",
    "results_count": 1,
    "session_id": "session_demo"
  }
}
```

Expected success (username):
```json
{
  "success": true,
  "data": [
    {
      "id": 987654321,
      "username": "testuser",
      "first_name": "Test",
      "last_name": "User",
      "phone": null,
      "is_bot": false,
      "is_verified": false,
      "is_restricted": false,
      "is_scam": false,
      "is_fake": false,
      "access_hash": 98765432109876543210,
      "photo": null,
      "status": "online",
      "created_at": "2025-08-26T09:12:20.556782",
      "updated_at": "2025-08-26T09:12:20.556791"
    }
  ],
  "errors": [],
  "metadata": {
    "search_type": "username",
    "query": "testuser",
    "results_count": 1,
    "session_id": "session_demo"
  }
}
```

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:

- Create an issue in the repository
- Contact the development team
- Check the documentation

## 🔄 Migration from Legacy

This refactored service maintains backward compatibility with the legacy system while providing a modern, maintainable architecture. Key improvements:

- **Unified API**: Single endpoint instead of multiple scripts
- **Standardized responses**: Consistent JSON format
- **Better error handling**: Comprehensive error responses
- **Modern dependencies**: Updated Python packages
- **Containerization**: Docker support with health checks
- **Testing**: Comprehensive test coverage
- **Documentation**: API documentation with examples

### Legacy Scripts

The following legacy scripts have been replaced by the unified API:

- `start_phone.py` → `/api/v1/telegram/search` with phone parameter
- `start_chat.py` → Future enhancement (not in current scope)

### Configuration Changes

- Environment variables remain the same
- Database connections unchanged
- Proxy configuration maintained
- Session management preserved