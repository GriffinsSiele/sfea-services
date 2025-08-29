import logging
import sys

REDIS_LOGGER = logging.getLogger("redis")
logger = REDIS_LOGGER
ch = logging.StreamHandler()
ch.setLevel(logging.DEBUG)
formatter = logging.Formatter(
    "%(asctime)s - %(name)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s"
)
ch.setFormatter(formatter)
ch.setStream(sys.stdout)
logger.addHandler(ch)
logger.propagate = False
