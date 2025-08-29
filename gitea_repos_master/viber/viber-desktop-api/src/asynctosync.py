import asyncio
from functools import wraps
from typing import Callable


def async_to_sync_func(func: Callable):
    """
    Function to convert async function into sync function
    """
    if not asyncio.iscoroutinefunction(func):
        raise TypeError(f"Function {func.__name__} is not async function")

    @wraps(func)
    def run(*args, **kwargs):
        coroutine = func(*args, **kwargs)
        loop = get_or_create_eventloop()
        if loop.is_running():
            return coroutine
        return loop.run_until_complete(coroutine)

    return run


def get_or_create_eventloop():
    try:
        return asyncio.get_event_loop()
    except RuntimeError as ex:
        if "There is no current event loop in thread" in str(ex):
            loop = asyncio.new_event_loop()
            asyncio.set_event_loop(loop)
            return asyncio.get_event_loop()
