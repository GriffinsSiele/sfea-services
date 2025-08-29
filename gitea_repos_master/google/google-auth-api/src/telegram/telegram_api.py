"""
Модуль для работы с телеграм ботом.

URL адрес для работы с телеграм ботом:
https://api.telegram.org/bot<token>/METHOD_NAME

Отправить сообщение:
https://api.telegram.org/bot<token>/sendMessage
Отправить файл:
https://api.telegram.org/bot<token>/sendDocument

В теле сообщения обязательно передаем "chat_id": self.chat_id

Пример отпавки файла из терминала:
curl -v -F "chat_id=569502265" -F document=@/Users/users/Desktop/file.txt https://api.telegram.org/bot<TOKEN>/sendDocument

"""

import requests

from src.interfaces.abstract_telegram_api import AbstractTelegramAPI


class TelegramAPI(AbstractTelegramAPI):
    def __init__(self, token: str, chat_id: str) -> None:
        self.chat_id = chat_id
        self.url = "https://api.telegram.org/bot" + token + "/"

    def send_file(self, file: str, send_as: str | None = None) -> str:
        files = {"document": (send_as, open(file, "rb")) if send_as else open(file, "rb")}
        data = {"chat_id": self.chat_id}

        response = self._send("post", self.url + "sendDocument", data=data, files=files)
        return response

    def send_message(self, message: str) -> str:
        data = {"chat_id": self.chat_id, "text": message}
        response = self._send("post", self.url + "sendMessage", data=data)
        return response

    @staticmethod
    def _send(method, url, data=None, files=None) -> str:
        if method == "post":
            response = requests.post(url, data=data, files=files)
        else:
            response = requests.get(url, params=data)
        return response.text
