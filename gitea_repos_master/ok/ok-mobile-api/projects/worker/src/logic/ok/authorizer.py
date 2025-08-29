import json
import logging
from typing import Any, Optional

from isphere_exceptions.session import (
    SessionBlocked,
    SessionCaptchaDecodeError,
    SessionInvalidCredentials,
    SessionLocked,
    SessionOutdated,
)
from isphere_exceptions.worker import UnknownError
from pydash import get
from rabbitmq_logic.config import MessageType
from rabbitmq_logic.publisher import RabbitMQPublisher

from lib.src.logic.base_authorizer import BaseAuthorizer
from lib.src.logic.validation import ResponseValidation
from lib.src.request_params.api.auth import AuthParams
from src.config.settings import MONGO_COLLECTION


class AuthorizerOK(BaseAuthorizer):
    def __init__(
        self,
        auth_data: Any = None,
        use_proxy=True,
        rabbitmq: Optional[RabbitMQPublisher] = None,
        logger=logging,
        *args,
        **kwargs,
    ):
        super().__init__(auth_data, use_proxy, logger, *args, **kwargs)
        self.rabbitmq = rabbitmq

    async def authorize(self) -> str:
        self.logging.info(f"Authorization: {self.get_session()}")

        auth_params = AuthParams(
            login=self.login,
            password=self.password,
            device=self.device,
            proxy=self.proxy,
            cookies=self.cookies,
        )
        try:
            response, response_o = await ResponseValidation.validate_request(
                auth_params, return_response_raw=True
            )
            self.session_key = get(response, "0.ok.session_key")
            self.cookies = dict(response_o.cookies)
            logging.info(f"Set session key: {self.session_key}")
            if not self.session_key:
                raise UnknownError(f"Сервер вернул не session_key: {response}")
        except (SessionOutdated, SessionInvalidCredentials) as e:
            self.logging.info(e)
            raise SessionBlocked(e)
        except (SessionCaptchaDecodeError, SessionLocked) as e:
            self.logging.error(e)
            if self.rabbitmq:
                await self.rabbitmq.add_task(
                    json.dumps(
                        {**self.get_session(), "collection": MONGO_COLLECTION},
                        default=str,
                    ),
                    content_type="application/json",
                    expiration=10 * 60,  # 600 sec = 10 min
                    type=MessageType.INNER_MESSAGE,
                )

            raise SessionLocked("Аккаунт отправлен на активацию")

        return self.session_key
