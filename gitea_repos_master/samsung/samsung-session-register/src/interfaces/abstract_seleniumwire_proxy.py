from abc import ABC, abstractmethod


class AbstractSeleniumWireProxy(ABC):
    """Интерфейс адаптера сервиса прокси, для использования в библиотеке seleniumwire"""

    @abstractmethod
    async def get_proxy(self) -> dict | None:
        pass
