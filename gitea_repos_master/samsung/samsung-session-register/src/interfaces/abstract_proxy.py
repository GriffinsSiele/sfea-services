from abc import ABC, abstractmethod


class AbstractProxy(ABC):
    """Интерфейс для сервиса прокси"""

    @abstractmethod
    async def get_proxy(self) -> dict | None:
        pass
