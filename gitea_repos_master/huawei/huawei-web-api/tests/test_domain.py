import pytest

from domain.services.phone_service import PhoneParseService
from domain.services.email_service import EmailParseService


def test_phone_normalization():
    svc = PhoneParseService()
    assert svc.normalize("7931") == "+7931"
    assert svc.normalize("+7931") == "+7931"


def test_phone_parse_valid():
    svc = PhoneParseService()
    assert svc.parse("+79319999999") == {"list__phones": ["79319999999"]}


def test_email_parse_valid():
    svc = EmailParseService()
    assert svc.parse("test@domain.com") == {"list__emails": ["test@domain.com"]}


