import logging
import sys


class Logger:
    def create(self):
        self._create_logger()

        logging.getLogger("pyalsa").setLevel(logging.CRITICAL)

    def _create_logger(self):
        logging.basicConfig(
            stream=sys.stdout,
            format="%(asctime)s - %(name)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s",
            level=logging.INFO,
        )

    def create_logger_worker(self, worker=None):
        logger = logging.getLogger("root" if worker is None else f"W-{worker:02d}")
        return logger
