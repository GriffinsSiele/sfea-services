import json
import logging

from putils_logic.putils import PUtils

from src.config.settings import TG_BOT_TOKEN, TG_CHAT_ID
from src.interfaces.abstract_telegram_api import AbstractTelegramAPI
from src.interfaces.abstract_telegram_bot import AbstractTelegramBot
from src.telegram.telegram_api import TelegramAPI


class BaseTelegramBot(AbstractTelegramBot):
    telegram_api: AbstractTelegramAPI
    message_prefix = ""

    def send_files_from_path(self, path: str, message: str) -> None:
        _prefix = ""
        if self.message_prefix:
            _prefix = self.message_prefix + " "
        try:
            filename = path.split("/")[-1]
            for file in PUtils.get_files(path):
                file_extension = file.split(".")[-1]
                response = self.telegram_api.send_file(
                    file, f"{_prefix}{filename}.{file_extension}"
                )
                if not json.loads(response).get("ok"):
                    logging.error(
                        f'Error when sending file "{file}" to telegram: {response}'
                    )
            self.telegram_api.send_message(f"{_prefix}{filename} search-key: {message}")

        except FileNotFoundError:
            logging.error(f'Path "{path}" not found')


class TelegramBot(BaseTelegramBot):
    telegram_api = TelegramAPI(token=TG_BOT_TOKEN, chat_id=TG_CHAT_ID)
