from pydash import get, set_


class SeleniumWireProxyAdapter:
    """Адаптер прокси, для использования в библиотеке seleniumwire"""

    @staticmethod
    def prepare_proxy(proxy: dict, no_proxy: str | None = "localhost,127.0.0.1") -> dict:
        """
        Возвращает прокси в требуемом Seleniumwire формате
        https://pypi.org/project/selenium-wire/#proxies

        Example:

        options = {
            'proxy': {
                'http': 'http://...',
                'https': 'http://...',
                'no_proxy': 'localhost,127.0.0.1',  # excludes
            }
        }
        """
        http = get(proxy, "http")
        https = get(proxy, "https")

        if not http or not https:
            raise ValueError('Proxy dict does not contain "http" or "https" keys')

        proxy_conf = {
            "proxy": {
                "http": http,
                "https": https,
            }
        }
        if no_proxy:
            set_(proxy_conf, "proxy.no_proxy", no_proxy)

        return proxy_conf
