from typing import Any

from proxy_manager.core import logger


class Singleton(type):
    _instances: Any = {}

    def __call__(cls, *args, **kwargs):
        if cls not in cls._instances:
            cls._instances[cls] = super(Singleton, cls).__call__(*args, **kwargs)
        return cls._instances[cls]


def fallback(func):
    async def wrapper(
        self, *args, repeat=None, fallback_query=None, query=None, **kwargs
    ):
        exception = None
        for _ in range(repeat or 1):
            try:
                return await func(self, *args, query=query, **kwargs)
            except Exception as e:
                exception = e

                if fallback_query:
                    try:
                        return await func(self, *args, query=fallback_query, **kwargs)
                    except Exception as e:
                        exception = e

        if exception:
            raise exception
        return None

    return wrapper
