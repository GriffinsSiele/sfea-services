import logging

from requests_logic.base import RequestBaseParamsAsync

from src.config.settings import CAPTCHA_SERVICE_URL


class CaptchaSolverNew:
    SOLUTION_TIMEOUT = 40

    @staticmethod
    async def solve(captcha_file):
        try:
            response = await CaptchaSolverNew.request(
                url=f"{CAPTCHA_SERVICE_URL}/api/decode/image",
                method="POST",
                params={
                    "provider": "capmonster",
                    "source": "ok-mobile-sessions",
                    "timeout": CaptchaSolverNew.SOLUTION_TIMEOUT,
                },
                data={"image": open(captcha_file, "rb")},
                timeout=CaptchaSolverNew.SOLUTION_TIMEOUT + 5,
                verify=False,
            )
            r = response.json()
            return r.get("text"), r.get("task_id")
        except Exception as e:
            logging.info(f"Error while getting solution: {e}")
        return "1", None

    @staticmethod
    async def request(*args, **kwargs):
        try:
            logging.info("Sending request to captcha service...")
            r = await RequestBaseParamsAsync(*args, **kwargs).request()
            logging.info(f"Captcha service response: {r}, {r.text}")
            return r
        except Exception as e:
            logging.info(f"Error while sending request to captcha service: {e}")
            raise e

    @staticmethod
    async def set_solving_status(task_id, status="reportbad"):
        if not task_id:
            return None
        try:
            r = await CaptchaSolverNew.request(
                url=f"{CAPTCHA_SERVICE_URL}/api/tasks/update",
                method="PUT",
                params={
                    "solved_status": "true" if status != "reportbad" else "false",
                    "task_id": task_id,
                },
                timeout=2,
                verify=False,
            )
        except Exception as e:
            pass
