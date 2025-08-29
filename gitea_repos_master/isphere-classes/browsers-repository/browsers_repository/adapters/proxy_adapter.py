import re
from typing import Collection

from pydash import get


class ProxyParserException(Exception):
    pass


class ProxyAdapter:
    """Обрабатывает входные данные прокси и приводит к единому формату для дальнейшего использования."""

    def __init__(self) -> None:
        self.adapted_proxy: dict = {}

    def adapt(self, proxy: dict[str, str | Collection[str]]) -> dict:
        """Преобразует входные параметры прокси в единый формат.
        На входе может быть только колюч http или https и url с параметрами простой аутентификации
        или словарь с параметрами "server", "port", "login", "password". На основании этих данных метод
        сформирует остальные параметры. Так же принимает необязательный параметр id.

        :param proxy: Входные параметры прокси.
        :return: Адаптированные данные прокси.

        Example:

        Полная запись, указаны все параметры
        proxy = {
            "http": "http://<простая аутентификация>...",
            "https": "http://<простая аутентификация>...",
            "id": "****",
            "server": "127.0.0.1",
            "port": "8080",
            "login": "****",
            "password": "****",
        }
        """
        self.adapted_proxy["http"] = get(proxy, "http")
        self.adapted_proxy["https"] = get(proxy, "https")
        self.adapted_proxy["server"] = get(proxy, "server")
        self.adapted_proxy["port"] = get(proxy, "port")
        self.adapted_proxy["login"] = get(proxy, "login")
        self.adapted_proxy["password"] = get(proxy, "password")
        self.adapted_proxy["id"] = get(proxy, "extra_fields.id")
        if not self.adapted_proxy["id"]:
            self.adapted_proxy["id"] = get(proxy, "id", "none")

        if all(self.adapted_proxy.values()):
            return self.adapted_proxy

        if self.adapted_proxy["http"] and not self.adapted_proxy["https"]:
            http = self.adapted_proxy["http"]
            if isinstance(http, str):
                https = http.replace("http", "https")
                self.adapted_proxy["https"] = https

        if self.adapted_proxy["https"] and not self.adapted_proxy["http"]:
            https = self.adapted_proxy["https"]
            if isinstance(https, str):
                http = https.replace("https", "http")
                self.adapted_proxy["http"] = http

        if not self.adapted_proxy["http"] and not self.adapted_proxy["https"]:
            if not self._check_params():
                raise ProxyParserException()
            self.adapted_proxy["http"] = self._make_http()
            self.adapted_proxy["https"] = self._make_https()

        if not self._check_params():
            self._get_params_from_simple_auth(self.adapted_proxy["http"])

        if all(self.adapted_proxy.values()):
            return self.adapted_proxy

        raise ProxyParserException()

    def _make_http(self) -> str:
        return (
            f'http://{self.adapted_proxy["login"]}:{self.adapted_proxy["password"]}'
            f'@{self.adapted_proxy["server"]}:{self.adapted_proxy["port"]}'
        )

    def _make_https(self) -> str:
        return (
            f'https://{self.adapted_proxy["login"]}:{self.adapted_proxy["password"]}'
            f'@{self.adapted_proxy["server"]}:{self.adapted_proxy["port"]}'
        )

    def _get_params_from_simple_auth(self, url: str):
        match = re.search(r"http[s]*://(\w+):(\w+)@(\d+\.\d+\.\d+\.\d+):(\d+)", url)
        if not match:
            raise ProxyParserException()
        if not self.adapted_proxy["login"]:
            self.adapted_proxy["login"] = match.group(1)
        if not self.adapted_proxy["password"]:
            self.adapted_proxy["password"] = match.group(2)
        if not self.adapted_proxy["server"]:
            self.adapted_proxy["server"] = match.group(3)
        if not self.adapted_proxy["port"]:
            self.adapted_proxy["port"] = match.group(4)

    def _check_params(self) -> bool:
        return all(
            (
                self.adapted_proxy["login"],
                self.adapted_proxy["password"],
                self.adapted_proxy["server"],
                self.adapted_proxy["port"],
            )
        )
