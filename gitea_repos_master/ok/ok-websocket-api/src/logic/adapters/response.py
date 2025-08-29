import re
from difflib import SequenceMatcher
from urllib.parse import parse_qs, urlparse

from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from pydash import get

from src.config.app import ConfigApp


class ResponseAdapter:
    @staticmethod
    def cast(response, payload):
        is_email = "@" in payload
        message = get(response, "payload.message.text", "")
        short_message = get(response, "payload.message.attaches.0.message", "")

        if (
            "не дал результатов" in message
            or "Диалог со Службой поддержки завершён" in short_message
            or "Не удалось определить подходящий профиль" in message
            or "Пожалуйста, воспользуйтесь подсказками и попробуйте прислать данные"
            in message
            or "В таком случае пришлите, пожалуйста" in message
        ):
            raise NoDataEvent("Не найден пользователь")

        if (
            "Речь идет о" in message
            or "Может этот аккаунт" in message
            or "Это нужный профиль" in message
            or "Проверьте, это он" in message
            or "Может, этот аккаунт" in message
        ):
            avatar = get(response, "payload.message.attaches.1.url")
            avatar_cropped = get(response, "payload.message.attaches.1.previewData")
            avatar, avatar_cropped = ResponseAdapter._check_avatar(avatar, avatar_cropped)

            city = get(re.findall("\nГород: (.*)\n", message), "0")
            register_date = get(
                re.findall("\nДата регистрации: ([\d\.]+)\n", message), "0"
            )
            register_year = get(
                re.findall("\nГод регистрации: ([\d\.]+)\n", message), "0"
            )
            phone_number = get(re.findall("\nТелефон: ([+\d*]+)\n", message), "0")
            user_name = get(message.split("\n"), "1")
            email = get(re.findall("\nE-mail: (.*)\n", message), "0")

            return {
                "phone_number": phone_number if is_email else None,
                "email": email if not is_email else None,
                "user_name": user_name,
                "city": city,
                "register_date": register_date,
                "register_year": register_year,
                "avatar": avatar,
                "avatar_cropped": avatar_cropped,
            }

        raise UnknownError(
            f"Выдан ответ, который не обрабатывается программой: {response}"
        )

    @staticmethod
    def _check_avatar(avatar, avatar_cropped):
        avatar = ResponseAdapter._get_avatar(avatar)
        return (avatar, avatar_cropped) if avatar else (None, None)

    @staticmethod
    def _get_avatar_hash(avatar):
        if not avatar or not avatar.startswith("http"):
            return None

        query = parse_qs(urlparse(avatar).query)
        return get(query, "r.0")

    @staticmethod
    def _get_avatar(avatar):
        avatar_hash = ResponseAdapter._get_avatar_hash(avatar)
        return (
            None
            if (not avatar_hash or avatar_hash in ConfigApp.DEFAULT_EMPTY_AVATAR_HASH)
            else avatar
        )

    @staticmethod
    def get_similar_option(options, value):
        max_sim, v = 0, get(options, "0")
        for option in options:
            sim = SequenceMatcher(None, option, value).ratio()
            if sim > max_sim:
                max_sim, v = sim, option
        return v
