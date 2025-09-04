import pytest
from unittest.mock import AsyncMock, patch

from controllers.unified_controller import UnifiedController
from domain.services.phone_service import PhoneParseService
from domain.services.email_service import EmailParseService


@pytest.mark.asyncio
async def test_parse_phone():
    """
    Test the parse_phone method of UnifiedController.
    """
    # Mock the phone service
    mock_phone_service = AsyncMock(spec=PhoneParseService)
    mock_phone_service.normalize.return_value = "+79319999999"
    mock_phone_service.parse.return_value = {"list__phones": ["79319999999"]}
    
    # Mock the email service (not used in this test)
    mock_email_service = AsyncMock(spec=EmailParseService)
    
    # Create controller with mocked services
    controller = UnifiedController(mock_phone_service, mock_email_service)
    
    # Call the method under test
    result = await controller.parse_phone({"value": "79319999999"})
    
    # Assertions
    assert "item" in result
    assert result["item"]["input"] == "79319999999"
    assert result["item"]["type"] == "phone"
    assert result["item"]["normalized"] == "+79319999999"
    assert result["item"]["data"] == {"list__phones": ["79319999999"]}
    assert result["item"]["result"] == "Найден"
    assert result["item"]["result_code"] == "FOUND"
    
    # Verify mocks were called correctly
    mock_phone_service.normalize.assert_called_once_with("79319999999")
    mock_phone_service.parse.assert_called_once_with("+79319999999")


@pytest.mark.asyncio
async def test_parse_email():
    """
    Test the parse_email method of UnifiedController.
    """
    # Mock the email service
    mock_email_service = AsyncMock(spec=EmailParseService)
    mock_email_service.normalize.return_value = "test@example.com"
    mock_email_service.parse.return_value = {"list__emails": ["test@example.com"]}
    
    # Mock the phone service (not used in this test)
    mock_phone_service = AsyncMock(spec=PhoneParseService)
    
    # Create controller with mocked services
    controller = UnifiedController(mock_phone_service, mock_email_service)
    
    # Call the method under test
    result = await controller.parse_email({"value": "test@example.com"})
    
    # Assertions
    assert "item" in result
    assert result["item"]["input"] == "test@example.com"
    assert result["item"]["type"] == "email"
    assert result["item"]["normalized"] == "test@example.com"
    assert result["item"]["data"] == {"list__emails": ["test@example.com"]}
    assert result["item"]["result"] == "Найден"
    assert result["item"]["result_code"] == "FOUND"
    
    # Verify mocks were called correctly
    mock_email_service.normalize.assert_called_once_with("test@example.com")
    mock_email_service.parse.assert_called_once_with("test@example.com")


@pytest.mark.asyncio
async def test_parse_unified_phone():
    """
    Test the parse_unified method of UnifiedController with a phone number.
    """
    with patch("infrastructure.validator.client.ValidatorClient") as mock_validator_class:
        # Mock the validator
        mock_validator = mock_validator_class.return_value
        mock_validator.detect = AsyncMock()
        mock_validator.detect.return_value = "phone"
        
        # Mock the phone service
        mock_phone_service = AsyncMock(spec=PhoneParseService)
        mock_phone_service.normalize.return_value = "+79319999999"
        mock_phone_service.parse.return_value = {"list__phones": ["79319999999"]}
        
        # Mock the email service (not used in this test)
        mock_email_service = AsyncMock(spec=EmailParseService)
        
        # Create controller with mocked services
        controller = UnifiedController(mock_phone_service, mock_email_service)
        
        # Call the method under test
        result = await controller.parse_unified({"value": "79319999999"})
        
        # Assertions
        assert "item" in result
        assert result["item"]["input"] == "79319999999"
        assert result["item"]["type"] == "phone"
        assert result["item"]["normalized"] == "+79319999999"
        assert result["item"]["data"] == {"list__phones": ["79319999999"]}
        assert result["item"]["result"] == "Найден"
        assert result["item"]["result_code"] == "FOUND"
        
        # Verify mocks were called correctly
        mock_validator.detect.assert_called_once_with("79319999999")
        mock_phone_service.normalize.assert_called_once_with("79319999999")
        mock_phone_service.parse.assert_called_once_with("+79319999999")


@pytest.mark.asyncio
async def test_parse_unified_email():
    """
    Test the parse_unified method of UnifiedController with an email.
    """
    with patch("infrastructure.validator.client.ValidatorClient") as mock_validator_class:
        # Mock the validator
        mock_validator = mock_validator_class.return_value
        mock_validator.detect = AsyncMock()
        mock_validator.detect.return_value = "email"
        
        # Mock the email service
        mock_email_service = AsyncMock(spec=EmailParseService)
        mock_email_service.normalize.return_value = "test@example.com"
        mock_email_service.parse.return_value = {"list__emails": ["test@example.com"]}
        
        # Mock the phone service (not used in this test)
        mock_phone_service = AsyncMock(spec=PhoneParseService)
        
        # Create controller with mocked services
        controller = UnifiedController(mock_phone_service, mock_email_service)
        
        # Call the method under test
        result = await controller.parse_unified({"value": "test@example.com"})
        
        # Assertions
        assert "item" in result
        assert result["item"]["input"] == "test@example.com"
        assert result["item"]["type"] == "email"
        assert result["item"]["normalized"] == "test@example.com"
        assert result["item"]["data"] == {"list__emails": ["test@example.com"]}
        assert result["item"]["result"] == "Найден"
        assert result["item"]["result_code"] == "FOUND"
        
        # Verify mocks were called correctly
        mock_validator.detect.assert_called_once_with("test@example.com")
        mock_email_service.normalize.assert_called_once_with("test@example.com")
        mock_email_service.parse.assert_called_once_with("test@example.com")


@pytest.mark.asyncio
async def test_parse_unified_unknown():
    """
    Test the parse_unified method of UnifiedController with an unknown type.
    """
    with patch("infrastructure.validator.client.ValidatorClient") as mock_validator_class:
        # Mock the validator
        mock_validator = mock_validator_class.return_value
        mock_validator.detect = AsyncMock()
        mock_validator.detect.return_value = "unknown"
        
        # Mock the services (not used in this test)
        mock_phone_service = AsyncMock(spec=PhoneParseService)
        mock_email_service = AsyncMock(spec=EmailParseService)
        
        # Create controller with mocked services
        controller = UnifiedController(mock_phone_service, mock_email_service)
        
        # Call the method under test
        result = await controller.parse_unified({"value": "invalid123"})
        
        # Assertions
        assert "item" in result
        assert result["item"]["input"] == "invalid123"
        assert result["item"]["type"] == "unknown"
        assert result["item"]["normalized"] is None
        assert result["item"]["data"] is None
        assert result["item"]["result"] == "Не найден"
        assert result["item"]["result_code"] == "NOT_FOUND"
        assert result["item"]["notes"] == ["Unsupported input type"]
        
        # Verify mocks were called correctly
        mock_validator.detect.assert_called_once_with("invalid123")
        mock_phone_service.normalize.assert_not_called()
        mock_email_service.normalize.assert_not_called()
