import logging

from livenessprobe_logic import HealthCheck


def safe(func):
    def wrapper(*args, **kwargs):
        try:
            response = func(*args, **kwargs)
            HealthCheck().checkpoint()
            return response
        except Exception as e:
            logging.error(e)

    return wrapper


def with_prefix(f):
    def wrapper(*args, **kwargs):
        title, message, tags = f(*args, **kwargs)
        from src.logic.telegram.messages import TelegramMessages

        output = TelegramMessages._prefix() + title + "\n\n" + message

        tags = " ".join([f"#{tag.replace('-', '_')}" for tag in tags])
        if tags:
            output += ("" if output.endswith("\n") else "\n") + tags

        return output

    return wrapper
