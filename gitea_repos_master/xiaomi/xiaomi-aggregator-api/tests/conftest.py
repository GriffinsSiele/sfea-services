import pytest
from fastapi.testclient import TestClient

from app.main import app


@pytest.fixture
def client():
    """
    Test client for the FastAPI application.
    """
    return TestClient(app)


@pytest.fixture
def mock_validator_client(mocker):
    """
    Mock the ValidatorClient to avoid external dependencies.
    """
    mock = mocker.patch("app.infrastructure.validator.client.ValidatorClient")
    mock_instance = mock.return_value
    mock_instance.detect_with_meta.return_value = {"type": "phone", "confidence": 0.9, "source": "test"}
    return mock_instance


@pytest.fixture
def mock_xiaomi_client(mocker):
    """
    Mock the XiaomiServiceClient to avoid external dependencies.
    """
    mock = mocker.patch("app.infrastructure.services.xiaomi_client.XiaomiServiceClient")
    mock_instance = mock.return_value
    mock_instance.parse_value.return_value = {
        "input": "79319999999",
        "type": "phone",
        "service": "xiaomi",
        "success": True,
        "found": True,
        "data": {"records": [{"result": "Найден", "result_code": "FOUND"}]}
    }
    return mock_instance
