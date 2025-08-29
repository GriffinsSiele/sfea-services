from abc import ABC, abstractmethod


class AbstractCaptchaService(ABC):
    @abstractmethod
    async def post_captcha(self, image: bytes, timeout: int = 0) -> dict | None:
        pass

    @abstractmethod
    async def result_report(self, task_id: str, correct: bool) -> bool:
        pass
