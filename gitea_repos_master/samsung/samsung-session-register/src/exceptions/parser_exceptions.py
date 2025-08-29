"""
Модуль содержит специфичные для данного приложения исключения
"""

from isphere_exceptions import ISphereException


class ParserException(ISphereException):
    """Ошибка парсера данных, полученных от сайта"""

    DEFAULT_MESSAGE = "Requests parser error, result is empty"
