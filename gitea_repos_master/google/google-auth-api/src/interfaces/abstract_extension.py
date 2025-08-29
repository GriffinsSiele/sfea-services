from abc import ABC, abstractmethod


class AbstractProxyExtension(ABC):
    @abstractmethod
    def prepare(self, host: str, port: int, user: str, password: str) -> None:
        pass

    @property
    @abstractmethod
    def directory(self) -> str:
        pass
