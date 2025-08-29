from abc import ABC, abstractmethod


class AbstractProxy(ABC):
    @abstractmethod
    async def get_proxy(self) -> dict | None:
        pass
