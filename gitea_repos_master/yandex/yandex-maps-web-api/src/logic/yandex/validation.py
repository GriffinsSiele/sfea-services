import logging

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyLocked
from isphere_exceptions.session import SessionCaptchaDetected, SessionLocked
from isphere_exceptions.worker import UnknownError
from pydash import get
from requests.exceptions import ProxyError

from src.logic.proxy.proxy import ProxyCacheManager


class ResponseValidation:
    @staticmethod
    def validate_response(response):
        if response.status_code >= 300:
            if response.status_code == 403 and "invalid_key" in response.text:
                raise SessionLocked(response.text)

            raise UnknownError(
                f"Response with unknown status code: {response.status_code}. Text: {response.text}"
            )

        try:
            response = response.json()
        except (
            ClientProxyConnectionError,
            ClientHttpProxyError,
            ProxyError,
            TimeoutError,
            ConnectionError,
        ) as e:
            logging.error(e)
            ProxyCacheManager.clear_cache()
            raise ProxyLocked(e)
        except Exception as e:
            raise UnknownError(f"Error in parse json: {e}. Text: {response.text}")

        logging.info(f"Response: {response}")

        if "csrfToken" in response:
            raise SessionLocked(response["csrfToken"])

        if get(response, "type") == "captcha":
            raise SessionCaptchaDetected("Captcha detected")

        if "data" not in response:
            raise UnknownError(f"Not valid response format: {response}")

        logging.info("Response is valid")
        return response
