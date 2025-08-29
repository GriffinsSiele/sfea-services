import logging

from python_rucaptcha.core.enums import ReCaptchaEnm
from python_rucaptcha.re_captcha import ReCaptcha

from src.config.app import ConfigApp
from src.config.settings import RU_CAPTCHA
from src.logic.doublegis.authorizer import Authorizer2GIS
from src.request_params.api.captcha import CaptchaSolve


class CaptchaManager(Authorizer2GIS):
    def __init__(self, auth_data):
        super().__init__(auth_data)

    async def activate(self):
        recaptcha = ReCaptcha(
            rucaptcha_key=RU_CAPTCHA,
            pageurl="https://captcha.2gis.ru/form",
            googlekey=ConfigApp.SITE_KEY,
            method=ReCaptchaEnm.USER_RECAPTCHA.value,
        )
        response = recaptcha.captcha_handler()
        logging.info(f"Recaptcha response: {response}")

        token = response.get("captchaSolve")
        if not token:
            return None

        await self._prepare_proxy()

        solver = CaptchaSolve(
            token=token, cookies=self.cookies, query=self.auth_query, proxy=self.proxy
        )
        response = await solver.request()
        logging.info(f"Response {response}, {response.text}")
        logging.info(f"Cookies: {response.cookies}")

        if "Капча не корректна, попробуйте еще раз" in response.text:
            logging.info("Invalid captcha solve")
            return None

        if "captcha" not in response.cookies:
            return None

        self.cookies = {**self.auth_query, "captcha": response.cookies["captcha"]}
        self.captcha_block = False
        return "ok"
