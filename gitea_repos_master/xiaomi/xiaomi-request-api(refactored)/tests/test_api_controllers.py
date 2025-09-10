import json
import pytest
from fastapi.testclient import TestClient
from unittest.mock import patch, AsyncMock

from app.main import app
from app.api.v1.schemas import ParseResponse, AggregatedResponse


@pytest.fixture
def client():
    return TestClient(app)


def test_parse_batch_endpoint(client):
    """
    Test the /api/v1/parse endpoint with mocked application service.
    """
    with patch("app.application.service.AggregatorApplicationService") as mock_service_class:
        # Setup mock
        mock_service = mock_service_class.return_value
        mock_service.parse_batch = AsyncMock()
        mock_service.parse_batch.return_value = AggregatedResponse(
            success=True,
            total=2,
            items=[
                ParseResponse(
                    input="79319999999",
                    type="phone",
                    service="xiaomi",
                    success=True,
                    found=True,
                    data={"records": [{"result": "Найден", "result_code": "FOUND"}]}
                ),
                ParseResponse(
                    input="test@example.com",
                    type="email",
                    service="xiaomi",
                    success=True,
                    found=True,
                    data={"records": [{"result": "Найден", "result_code": "FOUND"}]}
                )
            ]
        )
        
        # Make request
        response = client.post(
            "/api/v1/parse",
            json={"values": ["79319999999", "test@example.com"]}
        )
        
        # Assertions
        assert response.status_code == 200
        data = response.json()
        assert data["success"] is True
        assert data["total"] == 2
        assert len(data["items"]) == 2
        assert data["items"][0]["input"] == "79319999999"
        assert data["items"][1]["input"] == "test@example.com"
        
        # Verify mock was called correctly
        mock_service.parse_batch.assert_called_once_with(["79319999999", "test@example.com"])


def test_parse_xiaomi_endpoint(client):
    """
    Test the /api/v1/xiaomi/parse endpoint with mocked application service.
    """
    with patch("app.application.service.AggregatorApplicationService") as mock_service_class:
        # Setup mock
        mock_service = mock_service_class.return_value
        mock_service.parse_single = AsyncMock()
        mock_service.parse_single.return_value = ParseResponse(
            input="79319999999",
            type="phone",
            service="xiaomi",
            success=True,
            found=True,
            data={"records": [{"result": "Найден", "result_code": "FOUND"}]}
        )
        
        # Make request
        response = client.post(
            "/api/v1/xiaomi/parse",
            json={"value": "79319999999"}
        )
        
        # Assertions
        assert response.status_code == 200
        data = response.json()
        assert data["input"] == "79319999999"
        assert data["type"] == "phone"
        assert data["service"] == "xiaomi"
        assert data["success"] is True
        assert data["found"] is True
        assert data["data"] == {"records": [{"result": "Найден", "result_code": "FOUND"}]}
        
        # Verify mock was called correctly
        mock_service.parse_single.assert_called_once_with("79319999999")


def test_parse_batch_validation_error(client):
    """
    Test validation error handling in the /api/v1/parse endpoint.
    """
    # Make request with invalid payload
    response = client.post(
        "/api/v1/parse",
        json={"invalid_field": "value"}
    )
    
    # Assertions
    assert response.status_code == 422  # Unprocessable Entity
    data = response.json()
    assert "detail" in data


def test_parse_xiaomi_validation_error(client):
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
