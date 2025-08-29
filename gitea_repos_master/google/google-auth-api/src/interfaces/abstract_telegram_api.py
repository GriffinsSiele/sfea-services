from abc import ABC, abstractmethod


class AbstractTelegramAPI(ABC):
    @abstractmethod
    def send_file(self, file: str, send_as: str | None = None) -> str:
        """Отправляет файл в телеграм.

        :param file: Путь к файлу.
        :param send_as: Изменить имя отправляемого файла.
        :return: Ответ клиента.
        """
        pass

    @abstractmethod
    def send_message(self, message: str) -> str:
        """Отправляет сообщение в телеграм.

        :param message: Сообщение.
        :return: Ответ клиента.
        """
        pass
