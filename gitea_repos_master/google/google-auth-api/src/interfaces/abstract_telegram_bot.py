from abc import ABC, abstractmethod


class AbstractTelegramBot(ABC):
    message_prefix = ""

    @abstractmethod
    def send_files_from_path(self, path: str, message: str) -> None:
        pass
