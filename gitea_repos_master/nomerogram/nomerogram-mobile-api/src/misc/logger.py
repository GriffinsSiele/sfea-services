import logging


class Logger:
    def create(self):
        self._create_logger()

    def _create_logger(self):
        logging.basicConfig(
            format="%(asctime)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s",
            level=logging.INFO,
        )