import logging
import re

from isphere_exceptions.session import SessionCaptchaDecodeError
from isphere_exceptions.worker import UnknownError
from pydash import find, get
from requests.exceptions import ProxyError

from src.logic.captcha.captcha_cache import CaptchaCacheManager
from src.logic.captcha_new.captcha import CaptchaSolverNew
from src.request_params.api.captcha_get import CaptchaGet
from src.request_params.api.captcha_image import CaptchaImage
from src.request_params.api.captcha_init import CaptchaInit
from src.request_params.api.captcha_redirect import CaptchaRedirect
from src.request_params.api.captcha_solve import CaptchaSolve
from src.utils.utils import extract_query


class CaptchaManager:
    MAX_SOLVE_CAPTCHA_TRIES = 5

    def __init__(
        self,
        recovery_token,
        cookies,
        device=None,
        proxy=None,
        solver_class=CaptchaSolverNew,
    ):
        self.recovery_token = recovery_token
        self.session = cookies
        self.device = device
        self.proxy = proxy
        self.solver_class = solver_class

        self.stage_validators = {
            "1": self._validator_stage_1,
            "2": self._validator_stage_1_5,
            "3": self._validator_stage_2,
            "4": self._validator_stage_3,
            "5": self._validator_stage_4,
        }

        self.stage_action = {
            "1": self._stage_token_location_extraction,
            "2": self._stage_token_extraction,
            "3": self._stage_token_extraction,
            "4": self._stage_save_image,
            "5": self._stage_token_extraction,
        }

    async def solve(self):
        try:
            await self._solve()
            return True
        except Exception as e:
            logging.error(e)
        return False

    async def _solve(self):
        self.cookies = {}
        stage_1_req = CaptchaInit(
            recovery_token=self.recovery_token, proxy=self.proxy, device=self.device
        )
        token = await self._stage_process("1", stage_1_req, output="headers")

        stage_1_5_req = CaptchaRedirect(
            token=token, proxy=self.proxy, device=self.device, cookies=self.cookies
        )
        token = await self._stage_process("2", stage_1_5_req)

        stage_2_req = CaptchaGet(
            token=token, proxy=self.proxy, device=self.device, cookies=self.cookies
        )
        token = await self._stage_process("3", stage_2_req)

        counter, captcha_solved = 0, False
        while not captcha_solved:
            counter += 1
            logging.info(f"Try {counter} time to solve captcha")
            if counter > CaptchaManager.MAX_SOLVE_CAPTCHA_TRIES:
                raise SessionCaptchaDecodeError("Limit exceeded for solving captcha")

            stage_3_req = CaptchaImage(proxy=self.proxy, cookies=self.cookies)
            image = await self._stage_process("4", stage_3_req, output="content")

            logging.info(f"Saving captcha to: {image}")
            try:
                solution, task_id = await self.solver_class.solve(image)
            except Exception as e:
                logging.error(f"Error in solving captcha: {e}")
                solution, task_id = "текст", None

            logging.info(f"Captcha solution: {solution}")

            stage_4_req = CaptchaSolve(
                token=token, captcha_text=solution, proxy=self.proxy, cookies=self.cookies
            )
            try:
                token = await self._stage_process("5", stage_4_req)

                captcha_solved = bool(token)
                logging.info(f"Captcha solution is valid: {captcha_solved}")
            except ProxyError as e:
                captcha_solved = False
                logging.error(f"Exception due to proxy error: {e}")
            except Exception as e:
                captcha_solved = False
                await self.solver_class.set_solving_status(task_id, "reportbad")
                logging.error(f"Exception in solving captcha: {e}")
            finally:
                CaptchaCacheManager.set_result(
                    image, solution if captcha_solved else "XXXXXX"
                )
                if captcha_solved:
                    await self.solver_class.set_solving_status(task_id, "reportgood")

        # stage_5_req = CaptchaFinish(token=token, proxy=self.proxy)
        # await self._stage_process("5", stage_5_req, cookies)

    def _validate_text(self, response, variants):
        return find(variants, lambda v: v in response)

    def _validator_stage_1(self, response):
        return True

    def _validator_stage_1_5(self, response):
        return self._validate_text(response, ["Ok, got it!", "Ок, понятно!"])

    def _validator_stage_2(self, response):
        return self._validate_text(response, ["Готово!"])

    def _validator_stage_3(self, response):
        return b"JFIF" in response

    def _validator_stage_4(self, response):
        return self._validate_text(
            response, ["Thank you!", "Большое спасибо!", "Всё ОК!"]
        )

    def _stage_save_image(self, response):
        return CaptchaCacheManager.save(response)

    def _stage_token_location_extraction(self, response):
        form = re.findall("tkn=([\d]+)", get(response, "Location"))
        token = get(form, "0")
        return token

    def _stage_token_extraction(self, response):
        form_action_url = re.findall('<form action="(.*?)" method="post" cl', response)
        query = get(form_action_url, "0")
        return get(extract_query(query), "tkn.0")

    async def _stage_process(self, index, requester, output="text"):
        logging.info(f"Call stage {index}")

        try:
            response = await requester.request()
            match output:
                case "text":
                    response_o = response.text
                case "content":
                    response_o = response.content
                case "json":
                    response_o = response.json()
                case "headers":
                    response_o = response.headers
                case _:
                    response_o = response
        except (ConnectionError, ProxyError) as e:
            raise ProxyError(e)
        except Exception as e:
            raise UnknownError(f"Captcha request error: [{type(e)}] {e}")

        validator = get(self.stage_validators, index, lambda x: True)
        action = get(self.stage_action, index, lambda x: x)

        if not validator(response_o):
            raise SessionCaptchaDecodeError(
                f"Error in captcha solve stage {index}. Response: {response.text[:200]}, {response.headers}"
            )
        self.cookies = {**self.cookies, **response.cookies.get_dict()}
        return action(response_o)
