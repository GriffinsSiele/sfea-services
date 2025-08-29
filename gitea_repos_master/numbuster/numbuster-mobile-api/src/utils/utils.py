import logging
import random
from datetime import datetime, timedelta
from time import time


def random_boolean():
    return random.choice([True, False])


def get_closest_3_am():
    now = datetime.now()
    next_day = now.replace(hour=3, minute=0, second=1, microsecond=1)
    if now >= next_day:
        next_day += timedelta(days=1)
    return next_day, (next_day - now).total_seconds()


def timing(f):
    async def wrap(*args, **kwargs):
        ts = time()
        result = await f(*args, **kwargs)
        te = time()
        logging.info(f"Function {f.__name__} executes at {round(te-ts, 2)} sec")
        return result

    return wrap
