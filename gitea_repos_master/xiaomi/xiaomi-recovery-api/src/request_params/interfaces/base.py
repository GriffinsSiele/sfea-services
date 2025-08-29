from requests_logic.base import RequestBaseParamsAsync
from worker_classes.utils import short

from src.logger import logging


class BaseRequestParams(RequestBaseParamsAsync):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.method = "GET"
        self.timeout = 10
        self.verify = False

    async def request(self, *args, **kwargs):
        try:
            response = await self.session.request(*args, **self._request_args(), **kwargs)
            body = await response.read()
            logging.info(
                f"Status code: {response.status}, body: {short(str(body).strip())}"
            )
        except Exception as e:
            logging.warning(str(e))
            raise e
        else:
            return self._create_http_response(body, response)
        finally:
            await self.session.close()
