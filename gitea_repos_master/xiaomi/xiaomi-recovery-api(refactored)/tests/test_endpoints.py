import pytest
from fastapi.testclient import TestClient
from unittest.mock import patch, AsyncMock

from main import app
from controllers.unified_controller import UnifiedController


@pytest.fixture
def client():
    return TestClient(app)


def test_aggregate_parse_endpoint(client):
    """
    Test the /api/v1/xiaomi/parse endpoint with mocked controller and validator.
    """
    with patch("controllers.unified_controller.UnifiedController") as mock_controller_class, \
         patch("infrastructure.validator.client.ValidatorClient") as mock_validator_class:
        
        # Setup controller mock
        mock_controller = mock_controller_class.return_value
        mock_controller.parse_phone = AsyncMock()
        mock_controller.parse_phone.return_value = {
            "item": {
                "input": "79319999999",
                "type": "phone",
                "normalized": "+79319999999",
                "data": {"list__phones": ["79319999999"]},
                "result": "Найден",
                "result_code": "FOUND"
            }
        }
        
        mock_controller.parse_email = AsyncMock()
        mock_controller.parse_email.return_value = {
            "item": {
                "input": "test@example.com",
                "type": "email",
                "normalized": "test@example.com",
                "data": {"list__emails": ["test@example.com"]},
                "result": "Найден",
                "result_code": "FOUND"
            }
        }
        
        # Setup validator mock
        mock_validator = mock_validator_class.return_value
        mock_validator.detect = AsyncMock()
        mock_validator.detect.side_effect = ["phone", "email", "unknown"]
        
        # Make request
        response = client.post(
            "/api/v1/xiaomi/parse",
            json={"inputs": ["79319999999", "test@example.com", "invalid123"]}
        )
        
        # Assertions
        assert response.status_code == 200
        data = response.json()
        assert data["total"] == 3
        assert len(data["results"]) == 3
        
        # Phone result
        assert data["results"][0]["input"] == "79319999999"
        assert data["results"][0]["type"] == "phone"
        assert data["results"][0]["normalized"] == "+79319999999"
        assert data["results"][0]["result"] == "Найден"
        assert data["results"][0]["result_code"] == "FOUND"
        
        # Email result
        assert data["results"][1]["input"] == "test@example.com"
        assert data["results"][1]["type"] == "email"
        assert data["results"][1]["normalized"] == "test@example.com"
        assert data["results"][1]["result"] == "Найден"
        assert data["results"][1]["result_code"] == "FOUND"
        
        # Unknown type result
        assert data["results"][2]["input"] == "invalid123"
        assert data["results"][2]["type"] == "unknown"
        assert data["results"][2]["normalized"] is None
        assert data["results"][2]["result"] == "Не найден"
        assert data["results"][2]["result_code"] == "NOT_FOUND"
        assert data["results"][2]["notes"] == ["Unsupported input type"]
        
        # Verify mocks were called correctly
        assert mock_validator.detect.call_count == 3
        mock_controller.parse_phone.assert_called_once()
        mock_controller.parse_email.assert_called_once()


def test_phone_service_endpoint(client):
    """
    Test the /api/v1/xiaomi/phone/parse endpoint with mocked controller.
    """
    with patch("controllers.unified_controller.UnifiedController") as mock_controller_class:
        # Setup mock
        mock_controller = mock_controller_class.return_value
        mock_controller.parse_phone = AsyncMock()
        mock_controller.parse_phone.return_value = {
            "item": {
                "input": "79319999999",
                "type": "phone",
                "normalized": "+79319999999",
                "data": {"list__phones": ["79319999999"]},
                "result": "Найден",
                "result_code": "FOUND"
            }
        }
        
        # Make request
        response = client.post(
            "/api/v1/xiaomi/phone/parse",
            json={"value": "79319999999"}
        )
        
        # Assertions
        assert response.status_code == 200
        data = response.json()
        assert "item" in data
        assert data["item"]["input"] == "79319999999"
        assert data["item"]["type"] == "phone"
        assert data["item"]["normalized"] == "+79319999999"
        assert data["item"]["result"] == "Найден"
        assert data["item"]["result_code"] == "FOUND"
        
        # Verify mock was called correctly
        mock_controller.parse_phone.assert_called_once()


def test_email_service_endpoint(client):
    """
    Test the /api/v1/xiaomi/email/parse endpoint with mocked controller.
    """
    with patch("controllers.unified_controller.UnifiedController") as mock_controller_class:
        # Setup mock
        mock_controller = mock_controller_class.return_value
        mock_controller.parse_email = AsyncMock()
        mock_controller.parse_email.return_value = {
            "item": {
                "input": "test@example.com",
                "type": "email",
                "normalized": "test@example.com",
                "data": {"list__emails": ["test@example.com"]},
                "result": "Найден",
                "result_code": "FOUND"
            }
        }
        
        # Make request
        response = client.post(
            "/api/v1/xiaomi/email/parse",
            json={"value": "test@example.com"}
        )
        
        # Assertions
        assert response.status_code == 200
        data = response.json()
        assert "item" in data
        assert data["item"]["input"] == "test@example.com"
        assert data["item"]["type"] == "email"
        assert data["item"]["normalized"] == "test@example.com"
        assert data["item"]["result"] == "Найден"
        assert data["item"]["result_code"] == "FOUND"
        
        # Verify mock was called correctly
        mock_controller.parse_email.assert_called_once()


def test_aggregate_parse_validation_error(client):
    """
    Test validation error handling in the /api/v1/xiaomi/parse endpoint.
    """
    # Make request with invalid payload
    response = client.post(
        "/api/v1/xiaomi/parse",
        json={"invalid_field": "value"}
    )
    
    # Assertions
    assert response.status_code == 422  # Unprocessable Entity
    data = response.json()
    assert "detail" in data


def test_phone_service_validation_error(client):
    """
    Test validation error handling in the /api/v1/xiaomi/phone/parse endpoint.
    """
    # Make request with invalid payload
    response = client.post(
        "/api/v1/xiaomi/phone/parse",
        json={"invalid_field": "value"}
    )
    
    # Assertions
    assert response.status_code == 422  # Unprocessable Entity
    data = response.json()
    assert "detail" in data


def test_email_service_validation_error(client):
    """
    Test validation error handling in the /api/v1/xiaomi/email/parse endpoint.
    """
    # Make request with invalid payload
    response = client.post(
        "/api/v1/xiaomi/email/parse",
        json={"invalid_field": "value"}
    )
    
    # Assertions
    assert response.status_code == 422  # Unprocessable Entity
    data = response.json()
    assert "detail" in data
