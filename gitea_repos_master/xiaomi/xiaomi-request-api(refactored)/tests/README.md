# Tests for xiaomi-aggregator-api

This directory contains unit tests for the xiaomi-aggregator-api service, following the DDD structure.

## Test Structure

- `test_domain_models.py`: Tests for domain models
- `test_application_service.py`: Tests for application service layer
- `test_api_controllers.py`: Tests for API controllers/endpoints

## Running Tests

### Prerequisites

Make sure you have the following packages installed:

```bash
pip install pytest pytest-asyncio pytest-mock httpx
```

### Run Tests

From the xiaomi-aggregator-api directory:

```bash
# Run all tests
pytest -xvs tests/

# Run specific test file
pytest -xvs tests/test_domain_models.py
pytest -xvs tests/test_application_service.py
pytest -xvs tests/test_api_controllers.py

# Run with coverage
pytest --cov=app tests/
```

Or use the provided scripts:

```bash
# Using the test-specific script
chmod +x tests/run_tests.sh
./tests/run_tests.sh

# Or use the main start script with test parameter
chmod +x start.sh
./start.sh test
```

## Mocking Strategy

The tests use pytest fixtures to mock external dependencies:

- `mock_validator_client`: Mocks the ValidatorClient to avoid external API calls
- `mock_xiaomi_client`: Mocks the XiaomiServiceClient to avoid external API calls

This ensures tests are fast, reliable, and don't depend on external services.
