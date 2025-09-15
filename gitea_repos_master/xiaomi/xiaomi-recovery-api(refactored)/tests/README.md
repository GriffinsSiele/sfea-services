# Tests for xiaomi-web-api

This directory contains unit tests for the xiaomi-web-api service, following the DDD structure.

## Test Structure

- `test_endpoints.py`: Tests for API endpoints
- `test_unified_controller.py`: Tests for the unified controller

## Running Tests

### Prerequisites

Make sure you have the following packages installed:

```bash
pip install pytest pytest-asyncio pytest-mock httpx
```

### Run Tests

From the xiaomi-web-api directory:

```bash
# Run all tests
pytest -xvs tests/

# Run specific test file
pytest -xvs tests/test_endpoints.py
pytest -xvs tests/test_unified_controller.py

# Run with coverage
pytest --cov=. tests/
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

- `mock_unified_controller`: Mocks the UnifiedController to avoid domain service dependencies
- Domain services are mocked directly in the controller tests

This ensures tests are fast, reliable, and don't depend on external services.
