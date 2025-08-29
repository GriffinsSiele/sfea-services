from abc import ABC, abstractmethod

AdaptedResponse = list[dict[str, str | list]]


class ResponseAdapter(ABC):
    @staticmethod
    @abstractmethod
    def cast(response: dict) -> AdaptedResponse:
        pass
