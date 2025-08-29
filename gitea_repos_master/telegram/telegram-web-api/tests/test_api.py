"""
API endpoint tests for Telegram service
Tests the FastAPI endpoints and responses
"""

import pytest
import json
from fastapi.testclient import TestClient
from unittest.mock import Mock, AsyncMock
from src.application.dto.search_request import SearchRequest
from src.application.dto.search_response import SearchResponse
from src.application.dto.error_response import ErrorResponse
from src.domain.entities.telegram_user import TelegramUser
from src.domain.entities.telegram_session import TelegramSession
from src.domain.value_objects.phone_number import PhoneNumber
from src.domain.value_objects.username import Username
from src.interface.controllers.telegram_controller import TelegramSearchService, RateLimiter


class TestSearchEndpoint:
    """Test the search endpoint functionality"""
    
    @pytest.fixture
    def mock_search_service(self):
        """Mock search service for testing"""
        service = Mock()
        service.get_available_session = AsyncMock()
        service.validate_session_for_search = AsyncMock(return_value=True)
        service.update_session_after_search = AsyncMock()
        return service
    
    @pytest.fixture
    def mock_rate_limiter(self):
        """Mock rate limiter for testing"""
        limiter = Mock()
        limiter.check_rate_limit = AsyncMock(return_value=True)
        limiter.get_retry_after = AsyncMock(return_value=0)
        return limiter
    
    @pytest.fixture
    def test_client(self, mock_search_service, mock_rate_limiter):
        """Test client with dependency overrides"""
        from main import app
        app.dependency_overrides[TelegramSearchService] = lambda: mock_search_service
        app.dependency_overrides[RateLimiter] = lambda: mock_rate_limiter
        client = TestClient(app)
        yield client
        app.dependency_overrides.clear()
    
    def test_search_by_phone_success(self, test_client, mock_search_service):
        """Test successful phone search"""
        # Mock session
        mock_session = Mock()
        mock_session.id = "session_123"
        mock_search_service.get_available_session.return_value = mock_session
        
        # Mock user data
        mock_user = TelegramUser(
            id=123456789,
            username="testuser",
            first_name="Test",
            last_name="User",
            phone="79319999999",
            is_bot=False,
            is_verified=False,
            is_restricted=False,
            is_scam=False,
            is_fake=False,
            access_hash=12345678901234567890,
            photo=None,
            status="online",
            created_at=None,
            updated_at=None
        )
        
        # Mock search result
        mock_search_service.search_by_phone = AsyncMock(return_value=[mock_user])
        
        # Make request
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"phone": "79319999999"}
        )
        
        # Verify response
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is True
        assert data["data"] is not None
        assert len(data["data"]) == 1
        assert data["data"][0]["id"] == 123456789
        assert data["data"][0]["username"] == "testuser"
        assert data["data"][0]["phone"] == "79319999999"
        assert data["errors"] == []
        assert data["metadata"]["search_type"] == "phone"
        assert data["metadata"]["query"] == "79319999999"
        assert data["metadata"]["results_count"] == 1
        assert data["metadata"]["session_id"] == "session_123"
    
    def test_search_by_username_success(self, test_client, mock_search_service):
        """Test successful username search"""
        # Mock session
        mock_session = Mock()
        mock_session.id = "session_456"
        mock_search_service.get_available_session.return_value = mock_session
        
        # Mock user data
        mock_user = TelegramUser(
            id=987654321,
            username="testuser",
            first_name="Test",
            last_name="User",
            phone=None,
            is_bot=False,
            is_verified=False,
            is_restricted=False,
            is_scam=False,
            is_fake=False,
            access_hash=98765432109876543210,
            photo=None,
            status="online",
            created_at=None,
            updated_at=None
        )
        
        # Mock search result
        mock_search_service.search_by_username = AsyncMock(return_value=mock_user)
        
        # Make request
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"username": "testuser"}
        )
        
        # Verify response
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is True
        assert data["data"] is not None
        assert len(data["data"]) == 1
        assert data["data"][0]["id"] == 987654321
        assert data["data"][0]["username"] == "testuser"
        assert data["metadata"]["search_type"] == "username"
        assert data["metadata"]["query"] == "testuser"
    
    def test_search_validation_error_both_params(self, test_client):
        """Test validation error when both phone and username provided"""
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"phone": "79319999999", "username": "testuser"}
        )
        
        assert response.status_code == 200  # FastAPI returns 200 for validation errors in our case
        data = response.json()
        
        assert data["success"] is False
        assert "Cannot provide both phone and username" in data["error"]
        assert data["error_code"] == "VALIDATION_ERROR"
    
    def test_search_validation_error_no_params(self, test_client):
        """Test validation error when no parameters provided"""
        response = test_client.post(
            "/api/v1/telegram/search",
            json={}
        )
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is False
        assert "Must provide either phone or username" in data["error"]
        assert data["error_code"] == "VALIDATION_ERROR"
    
    def test_search_validation_error_invalid_phone(self, test_client):
        """Test validation error for invalid phone format"""
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"phone": "invalid_phone"}
        )
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is False
        assert "Invalid phone number format" in data["error"]
        assert data["error_code"] == "VALIDATION_ERROR"
    
    def test_search_validation_error_invalid_username(self, test_client):
        """Test validation error for invalid username format"""
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"username": "ab"}  # Too short
        )
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is False
        assert "Invalid username format" in data["error"]
        assert data["error_code"] == "VALIDATION_ERROR"
    
    def test_search_no_available_session(self, test_client, mock_search_service):
        """Test error when no available session"""
        mock_search_service.get_available_session.return_value = None
        
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"phone": "79319999999"}
        )
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is False
        assert "No available sessions for search" in data["errors"]
    
    def test_search_session_cannot_perform(self, test_client, mock_search_service):
        """Test error when session cannot perform search"""
        mock_session = Mock()
        mock_session.id = "session_123"
        mock_search_service.get_available_session.return_value = mock_session
        mock_search_service.validate_session_for_search.return_value = False
        
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"phone": "79319999999"}
        )
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is False
        assert "Session cannot perform search at this time" in data["errors"]
    
    def test_search_rate_limit_exceeded(self, test_client, mock_rate_limiter):
        """Test rate limit exceeded error"""
        mock_rate_limiter.check_rate_limit.return_value = False
        mock_rate_limiter.get_retry_after.return_value = 3600
        
        response = test_client.post(
            "/api/v1/telegram/search",
            json={"phone": "79319999999"}
        )
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["success"] is False
        assert "Rate limit exceeded" in data["error"]
        assert data["error_code"] == "RATE_LIMIT_EXCEEDED"
        assert data["details"]["retry_after"] == 3600


class TestHealthEndpoints:
    """Test health and information endpoints"""
    
    @pytest.fixture
    def test_client(self):
        """Test client without mocking"""
        from main import app
        return TestClient(app)
    
    def test_root_endpoint(self, test_client):
        """Test root endpoint"""
        response = test_client.get("/")
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["service"] == "Telegram Search API"
        assert data["version"] == "1.0.0"
        assert data["status"] == "running"
        assert "search" in data["endpoints"]
        assert "health" in data["endpoints"]
    
    def test_health_endpoint(self, test_client):
        """Test health endpoint"""
        response = test_client.get("/health")
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["status"] == "healthy"
        assert data["service"] == "telegram-api"
        assert data["version"] == "1.0.0"
    
    def test_telegram_health_endpoint(self, test_client):
        """Test Telegram-specific health endpoint"""
        response = test_client.get("/api/v1/telegram/health")
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["status"] == "healthy"
        assert data["service"] == "telegram-api"
        assert data["version"] == "1.0.0"
    
    def test_telegram_root_endpoint(self, test_client):
        """Test Telegram root endpoint"""
        response = test_client.get("/api/v1/telegram/")
        
        assert response.status_code == 200
        data = response.json()
        
        assert data["service"] == "Telegram Search API"
        assert data["version"] == "1.0.0"
        assert "search" in data["endpoints"]


class TestErrorHandling:
    """Test error handling and responses"""
    
    @pytest.fixture
    def test_client(self):
        """Test client without mocking"""
        from main import app
        return TestClient(app)
    
    def test_404_error(self, test_client):
        """Test 404 error handling"""
        response = test_client.get("/nonexistent")
        
        assert response.status_code == 404
        data = response.json()
        
        assert data["success"] is False
        assert "Not Found" in data["error"]
        assert data["error_code"] == "HTTP_404"
    
    def test_405_method_not_allowed(self, test_client):
        """Test method not allowed error"""
        response = test_client.get("/api/v1/telegram/search")
        
        assert response.status_code == 405
        data = response.json()
        
        assert data["success"] is False
        assert "Method Not Allowed" in data["error"]
        assert data["error_code"] == "HTTP_405"
    
    def test_invalid_json_request(self, test_client):
        """Test invalid JSON request handling"""
        response = test_client.post(
            "/api/v1/telegram/search",
            data="invalid json",
            headers={"Content-Type": "application/json"}
        )
        
        # FastAPI should return 422 for invalid JSON
        assert response.status_code == 422


class TestRequestValidation:
    """Test request validation logic"""
    
    def test_phone_number_validation(self):
        """Test phone number validation in requests"""
        from src.interface.middleware.validation import validate_search_request
        
        # Valid phone
        request = SearchRequest(phone="79319999999")
        result = validate_search_request(request)
        assert result["valid"] is True
        
        # Invalid phone
        request = SearchRequest(phone="invalid")
        result = validate_search_request(request)
        assert result["valid"] is False
        assert "Invalid phone number format" in result["error"]
    
    def test_username_validation(self):
        """Test username validation in requests"""
        from src.interface.middleware.validation import validate_search_request
        
        # Valid username
        request = SearchRequest(username="testuser")
        result = validate_search_request(request)
        assert result["valid"] is True
        
        # Invalid username
        request = SearchRequest(username="ab")
        result = validate_search_request(request)
        assert result["valid"] is False
        assert "Invalid username format" in result["error"]
    
    def test_mutual_exclusion_validation(self):
        """Test mutual exclusion validation"""
        from src.interface.middleware.validation import validate_search_request
        
        # Both parameters
        request = SearchRequest(phone="79319999999", username="testuser")
        result = validate_search_request(request)
        assert result["valid"] is False
        assert "Cannot provide both phone and username" in result["error"]
        
        # No parameters
        request = SearchRequest()
        result = validate_search_request(request)
        assert result["valid"] is False
        assert "Must provide either phone or username" in result["error"]


if __name__ == "__main__":
    pytest.main([__file__])
