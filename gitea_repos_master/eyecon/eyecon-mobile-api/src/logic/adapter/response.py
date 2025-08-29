import base64
import logging

from isphere_exceptions.success import NoDataEvent
from pydash import get


class ResponseAdapter:
    @staticmethod
    def cast(response):
        if not response:
            raise NoDataEvent("No output")

        return [ResponseAdapter.cast_user(user) for user in response]

    @staticmethod
    def cast_user(user):
        name = get(user, "name")
        suspicious_spam = get(user, "suspicious_spam", False)

        if not name and not suspicious_spam:
            raise NoDataEvent("No output")

        type = get(user, "type", "")
        is_spam = ResponseAdapter.cast_bool("spam" in str(type) or suspicious_spam)

        if type or len(user.keys()) > 2:
            logging.info(f"Extra field: {user}")

        return {"name": name, "type": type, "is_spam": is_spam}

    @staticmethod
    def cast_bool(v):
        return "Да" if v else "Нет" if v is False else v

    @staticmethod
    def image(content):
        base64_content = base64.b64encode(content).decode()
        return f"data:image/png;base64,{base64_content}"

    @staticmethod
    def merge(responses):
        users, image = responses
        if not image or not users:
            return users
        if users:
            users[0]["image"] = image
        return users
