import pytest
from app.domain.models import ParsedResult


def test_parsed_result_creation():
    """
    Test creating a ParsedResult with default values.
    """
    result = ParsedResult(input="79319999999", type="phone")
    
    assert result.input == "79319999999"
    assert result.type == "phone"
    assert result.service == "xiaomi"
    assert result.success is False
    assert result.found is None
    assert result.data is None
    assert result.error is None
    assert result.error_code is None


def test_parsed_result_with_custom_values():
    """
    Test creating a ParsedResult with custom values.
    """
    result = ParsedResult(
        input="test@example.com",
        type="email",
        service="xiaomi",
        success=True,
        found=True,
        data={"records": [{"result": "Найден", "result_code": "FOUND"}]},
        error=None,
        error_code=None,
    )
    
    assert result.input == "test@example.com"
    assert result.type == "email"
    assert result.service == "xiaomi"
    assert result.success is True
    assert result.found is True
    assert result.data == {"records": [{"result": "Найден", "result_code": "FOUND"}]}
    assert result.error is None
    assert result.error_code is None


def test_parsed_result_with_error():
    """
    Test creating a ParsedResult with error information.
    """
    result = ParsedResult(
        input="invalid",
        type="unknown",
        service="xiaomi",
        success=False,
        found=None,
        data=None,
        error="Unsupported data type",
        error_code="TYPE_UNSUPPORTED",
    )
    
    assert result.input == "invalid"
    assert result.type == "unknown"
    assert result.service == "xiaomi"
    assert result.success is False
    assert result.found is None
    assert result.data is None
    assert result.error == "Unsupported data type"
    assert result.error_code == "TYPE_UNSUPPORTED"
