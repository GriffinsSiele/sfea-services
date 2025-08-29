from typing import Any, Optional

from aiohttp import ClientSession

from src.common.utils import SingletonLogging


class TelegramService(SingletonLogging):
    HOST = "https://api.telegram.org"
    TRANSLATE_MAP = {
        # Ключи для перевода частей сообщений, полученных в сервисе captcha cleaner
        "image_task_nnetworks": "Очистка задач изображений нейросетей",
        "image_task_!nnetworks": "Очистка задач изображений внешних провайдеров",
        "token_task": "Очистка токенов",
        "removed": "Удалено записей",
        "date_from": "Старше по времени, чем",
        "description": "Описание ошибки",
        "s3_images": "Изображения в S3",
        "status": "Статус",
        "Success": "Успех",
        "Fail": "Ошибка",
    }

    def __init__(self, chat_id: int, token: str) -> None:
        super().__init__()
        self.__chat_id = chat_id
        self.__msg_url = f"{self.HOST}/bot{token}/sendMessage"

    def _translate(self, s: str) -> str:
        return self.TRANSLATE_MAP.get(s) or s

    def _process_dict_msg_part(self, d: dict[str, Any]) -> str:
        text = ""
        for k, v in d.items():
            if isinstance(v, dict):
                text += f"{self._translate(k)}\n{self._process_dict_msg_part(v)}"
            else:
                text += f"{self._translate(k)} :  {self._translate(v)}\n"

        return text

    def _prepare_msg(self, msg_data: tuple[str | dict[str, Any]]) -> str:
        text = ""
        for msg_part in msg_data:
            if isinstance(msg_part, str):
                text += self._translate(msg_part)
            elif isinstance(msg_part, dict):
                text += f"{self._process_dict_msg_part(msg_part)}\n"

        return text

    async def send(
        self, msg_data: tuple[str | dict[str, Any]]
    ) -> Optional[dict[str, Any]]:
        data = {"chat_id": self.__chat_id, "text": self._prepare_msg(msg_data)}

        try:
            async with ClientSession() as session:
                async with session.post(
                    url=self.__msg_url,
                    data=data,
                    verify_ssl=False,
                    timeout=10,
                ) as response:
                    return await response.json(content_type=None)
        except Exception as exc:
            self.logger.info(
                f"Error while connectiong to host {self.HOST}. Detail: {exc.message if hasattr(exc, 'message') else exc.__str__()}"
            )
            return None
