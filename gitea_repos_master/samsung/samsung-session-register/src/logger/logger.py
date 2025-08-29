import logging

from worker_classes.logger import Logger as L


class Logger(L):
    """Класс логера.
    Содержит метода настройки логера для текущего приложения.
    """

    selenium_loggers = [
        "seleniumwire.handler",
        "seleniumwire.server",
        "seleniumwire.storage",
        "seleniumwire.backend",
        "mongo",
        "pymongo.serverSelection",
        "pymongo.command",
    ]

    def create(self, sensitive_fields=None) -> None:
        """Настраивает логер

        :param sensitive_fields: Список чувствительных полей, которые логер будет скрывать.
        :return: None
        """
        super().create(sensitive_fields)
        self._set_selenium()

    def _set_selenium(self) -> None:
        """Переводит логеры seleniumwire на уровень WARNING

        :return: None
        """
        for log in self.selenium_loggers:
            logging.getLogger(log).setLevel(logging.WARNING)
