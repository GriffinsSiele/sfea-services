import logging

import requests

from src.config.settings import CAPTCHA_SERVICE_URL


class CaptchaSolver:
    SOLUTION_TIMEOUT = 40

    @staticmethod
    def solve(captcha_url):

        try:
            response = CaptchaSolver.request(
                url=f"{CAPTCHA_SERVICE_URL}/api/decode/url",
                method="POST",
                params={
                    "provider": "rucaptcha",
                    "source": "ok-websocket",
                    "timeout": CaptchaSolver.SOLUTION_TIMEOUT,
                },
                json={"file_url": captcha_url},
                timeout=CaptchaSolver.SOLUTION_TIMEOUT + 5,
                verify=False,
            )

            r = response.json()
            return r.get("text"), r.get("task_id")
        except Exception as e:
            logging.info(f"Error while getting solution: {e}")
        return "1", None

    @staticmethod
    def request(*args, **kwargs):
        try:
            logging.info("Sending request to captcha service...")
            r = requests.request(*args, **kwargs)
            logging.info(f"Captcha service response: {r}, {r.text}")
            return r
        except Exception as e:
            logging.info(f"Error while sending request to captcha service: {e}")
            raise e

    @staticmethod
    def report(task_id, is_success=True):
        if not task_id:
            return None
        try:
            r = CaptchaSolver.request(
                url=f"{CAPTCHA_SERVICE_URL}/api/tasks/update",
                method="PUT",
                params={"solved_status": is_success, "task_id": task_id},
                timeout=2,
                verify=False,
            )
        except Exception as e:
            pass
