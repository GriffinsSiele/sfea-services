from abc import ABC, abstractmethod


class AbstractCaptchaService(ABC):
    @abstractmethod
    def solve_captcha(self, image: bytes, timeout: int) -> dict | None:
        pass

    @abstractmethod
    def solve_report(self, task_id: str, correct: bool) -> dict | str | None:
        pass
