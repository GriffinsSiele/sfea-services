import json
import logging

from isphere_exceptions.session import (
    SessionBlocked,
    SessionCaptchaDecodeError,
    SessionInvalidCredentials,
    SessionLocked,
    SessionOutdated,
)
from isphere_exceptions.worker import UnknownError
from pydash import get

from lib.src.logic.base_authorizer import BaseAuthorizer
from lib.src.logic.validation import ResponseValidation
from lib.src.request_params.api.auth import AuthParams
from src.config.settings import PROXY_URL
from src.logic.captcha.captcha_manager import CaptchaManager
from src.logic.captcha_new.captcha import CaptchaSolverNew


class SessionActivation(BaseAuthorizer):
    def __init__(self, auth_data, use_proxy=True):
        super().__init__(auth_data, use_proxy)
        self.captcha_solved = False

    async def activate(self):
        await self.set_proxy()

        logging.info(f"Authorization: {self.get_session()}")

        auth_params = AuthParams(
            login=self.login,
            password=self.password,
            device=self.device,
            proxy=self.proxy,
        )
        try:
            response = await ResponseValidation.validate_request(auth_params)
            self.session_key = get(response, "0.ok.session_key")
            logging.info(f"Set session key: {self.session_key}")
            if not self.session_key:
                raise UnknownError(f"Сервер вернул не session_key: {response}")
        except (SessionOutdated, SessionInvalidCredentials) as e:
            logging.info(e)
            raise SessionBlocked(e)
        except (SessionCaptchaDecodeError, SessionLocked) as e:
            logging.info(e)
            if not self.captcha_solved:
                await self.solve_captcha(json.loads(e.message), {})
            else:
                raise SessionCaptchaDecodeError("Не смогли решить капчу")

        return self.session_key

    async def solve_captcha(self, response, cookies):
        recovery_token = get(response, "custom_error.verify_required.restore_token")
        if not recovery_token:
            logging.error(f"Incorrect response for extracting token: {response}")

            ban_account = get(response, "custom_error.unblock_required.restore_token")
            if ban_account:
                raise SessionBlocked("Account blocked, SMS required")
            self.captcha_solved = True
        else:
            self.captcha_solved = await CaptchaManager(
                recovery_token,
                cookies,
                self.device,
                self.proxy,
                solver_class=CaptchaSolverNew,
            ).solve()

        logging.info(f"Captcha solved: {self.captcha_solved}")
        await self.activate()
