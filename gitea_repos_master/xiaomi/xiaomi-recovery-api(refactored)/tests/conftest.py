import pytest
from fastapi.testclient import TestClient

from main import app


@pytest.fixture
def client():
    """
    Test client for the FastAPI application.
    """
    return TestClient(app)


@pytest.fixture
def mock_unified_controller(mocker):
    """
    Mock the UnifiedController to avoid external dependencies.
    """
    mock = mocker.patch("controllers.unified_controller.UnifiedController")
    mock_instance = mock.return_value
    
    # Mock parse_phone method
    mock_instance.parse_phone.return_value = {
        "item": {
            "input": "79319999999",
            "type": "phone",
            "normalized": "+79319999999",
            "data": {"list__phones": ["79319999999"]},
            "result": "Найден",
            "result_code": "FOUND"
        }
    }
    
    # Mock parse_email method
    mock_instance.parse_email.return_value = {
        "item": {
            "input": "test@example.com",
            "type": "email",
            "normalized": "test@example.com",
            "data": {"list__emails": ["test@example.com"]},
            "result": "Найден",
            "result_code": "FOUND"
        }
    }
    
    # Mock parse_unified method
    mock_instance.parse_unified.return_value = {
        "item": {
            "input": "79319999999",
            "type": "phone",
            "normalized": "+79319999999",
            "data": {"list__phones": ["79319999999"]},
            "result": "Найден",
            "result_code": "FOUND"
        }
    }
    
    return mock_instance
