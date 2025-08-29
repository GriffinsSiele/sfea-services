from abc import ABC, abstractmethod

from src.logic.repository.screen_repository import ScreenRepository


class AbstractScreenRepositoryConfigurator(ABC):
    """Создает хранилище экранов и настраивает конфигурацию экранов для данного проекта."""

    @staticmethod
    @abstractmethod
    def make() -> ScreenRepository:
        """Возвращает настроенный репозиторий экранов для данного проекта.

        :return: ScreenRepository.
        """
        pass
