import pytest
from unittest.mock import AsyncMock, patch

from app.application.service import AggregatorApplicationService
from app.api.v1.schemas import ParseResponse, AggregatedResponse


@pytest.mark.asyncio
async def test_parse_single_phone():
    """
    Test parsing a single phone number with mocked dependencies.
    """
    with patch("app.infrastructure.validator.client.ValidatorClient") as mock_validator, \
         patch("app.infrastructure.services.xiaomi_client.XiaomiServiceClient") as mock_xiaomi:
        
        # Setup mocks
        validator_instance = mock_validator.return_value
        validator_instance.detect_with_meta = AsyncMock()
        validator_instance.detect_with_meta.return_value = {"type": "phone", "confidence": 0.9}
        
        xiaomi_instance = mock_xiaomi.return_value
        xiaomi_instance.parse_value = AsyncMock()
        xiaomi_instance.parse_value.return_value = ParseResponse(
            input="79319999999",
            type="phone",
            service="xiaomi",
            success=True,
            found=True,
            data={"records": [{"result": "Найден", "result_code": "FOUND"}]}
        )
        
        # Test the service
        service = AggregatorApplicationService()
        result = await service.parse_single("79319999999")
        
        # Assertions
        validator_instance.detect_with_meta.assert_called_once_with("79319999999")
        xiaomi_instance.parse_value.assert_called_once_with(value="79319999999", dtype="phone")
        assert result.input == "79319999999"
        assert result.type == "phone"
        assert result.service == "xiaomi"
        assert result.success is True
        assert result.found is True
        assert result.data == {"records": [{"result": "Найден", "result_code": "FOUND"}]}


@pytest.mark.asyncio
async def test_parse_single_email():
    """
    Test parsing a single email with mocked dependencies.
    """
    with patch("app.infrastructure.validator.client.ValidatorClient") as mock_validator, \
         patch("app.infrastructure.services.xiaomi_client.XiaomiServiceClient") as mock_xiaomi:
        
        # Setup mocks
        validator_instance = mock_validator.return_value
        validator_instance.detect_with_meta = AsyncMock()
        validator_instance.detect_with_meta.return_value = {"type": "email", "confidence": 0.9}
        
        xiaomi_instance = mock_xiaomi.return_value
        xiaomi_instance.parse_value = AsyncMock()
        xiaomi_instance.parse_value.return_value = ParseResponse(
            input="test@example.com",
            type="email",
            service="xiaomi",
            success=True,
            found=True,
            data={"records": [{"result": "Найден", "result_code": "FOUND"}]}
        )
        
        # Test the service
        service = AggregatorApplicationService()
        result = await service.parse_single("test@example.com")
        
        # Assertions
        validator_instance.detect_with_meta.assert_called_once_with("test@example.com")
        xiaomi_instance.parse_value.assert_called_once_with(value="test@example.com", dtype="email")
        assert result.input == "test@example.com"
        assert result.type == "email"
        assert result.service == "xiaomi"
        assert result.success is True
        assert result.found is True


@pytest.mark.asyncio
async def test_parse_single_unsupported_type():
    """
    Test parsing a value with unsupported type.
    """
    with patch("app.infrastructure.validator.client.ValidatorClient") as mock_validator:
        
        # Setup mocks
        validator_instance = mock_validator.return_value
        validator_instance.detect_with_meta = AsyncMock()
        validator_instance.detect_with_meta.return_value = {"type": "unknown", "confidence": 0.2}
        
        # Test the service
        service = AggregatorApplicationService()
        result = await service.parse_single("invalid123")
        
        # Assertions
        validator_instance.detect_with_meta.assert_called_once_with("invalid123")
        assert result.input == "invalid123"
        assert result.type == "unknown"
        assert result.service == "none"
        assert result.success is False
        assert result.found is None
        assert result.error == "Unsupported data type"
        assert result.error_code == "TYPE_UNSUPPORTED"


@pytest.mark.asyncio
async def test_parse_batch():
    """
    Test parsing a batch of values.
    """
    with patch.object(AggregatorApplicationService, "parse_single") as mock_parse_single:
        
        # Setup mocks
        mock_parse_single.side_effect = [
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
            ),
            ParseResponse(
                input="invalid123",
                type="unknown",
                service="none",
                success=False,
                found=None,
                error="Unsupported data type",
                error_code="TYPE_UNSUPPORTED"
            )
        ]
        
        # Test the service
        service = AggregatorApplicationService()
        result = await service.parse_batch(["79319999999", "test@example.com", "invalid123"])
        
        # Assertions
        assert mock_parse_single.call_count == 3
        assert isinstance(result, AggregatedResponse)
        assert result.success is True
        assert result.total == 3
        assert len(result.items) == 3
        assert result.items[0].input == "79319999999"
        assert result.items[1].input == "test@example.com"
        assert result.items[2].input == "invalid123"
