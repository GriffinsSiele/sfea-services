import requests
from pydash import get, map_


class ProxyManager:
    url = "https://i-sphere.ru/2.00/get_proxies.php"
    query = {"country": "ru", "status": "1", "limit": "1", "order": "lasttime"}

    def __init__(self, login=None, password=None):
        self.login = login
        self.password = password

    def _get_proxy(self, query=None, proxy_group=None):
        self.query = {**self.query, **(query if query else {})}
        if proxy_group:
            self.query["proxygroup"] = proxy_group
        return requests.get(self.url, params=self.query, auth=(self.login, self.password))

    def get_proxy(self, query=None, proxy_group=None, fallback_query=None):
        try:
            response = self._get_proxy(query, proxy_group)
            return ProxyManager._adapter_one(get(response.json(), "0"))
        except ValueError:
            if fallback_query:
                return self._resolve_not_found(fallback_query)

    def get_proxies(self, query=None, proxy_group=None):
        if "limit" not in query:
            query["limit"] = 100
        response = self._get_proxy(query, proxy_group)
        return ProxyManager._adapter_many(response.json())

    @staticmethod
    def _adapter_many(response):
        return map_(response, lambda p: ProxyManager._adapter_one(p))

    @staticmethod
    def _adapter_one(proxy):
        if not proxy:
            raise ValueError("No proxy returned")

        server = get(proxy, "server")
        port = get(proxy, "port")
        login = get(proxy, "login")
        password = get(proxy, "password")

        url = f"{login}:{password}@{server}:{port}"

        return {
            "http": "http://" + url,
            "https": "http://" + url,
            "server": server,
            "port": port,
            "login": login,
            "password": password,
            "extra_fields": proxy,
        }

    def _resolve_not_found(self, fallback_query=None):
        if fallback_query is None:
            fallback_query = {}
        if "id" in self.query:
            del self.query["id"]
        return self.get_proxy(fallback_query)
