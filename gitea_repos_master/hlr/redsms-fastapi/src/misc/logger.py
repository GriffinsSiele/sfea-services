import logging


class Logger:
    def create(self):
        self._create_logger()
        self._remove_unused_loggers()

    def _create_logger(self):
        logging.basicConfig(
            format="%(asctime)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)d) - %(message)s",
            level=logging.INFO,
        )

    def _remove_unused_loggers(self):
        disable_logging = ['uvicorn.error', 'uvicorn.access']
        for process in disable_logging:
            logger = logging.getLogger(process)
            logger.setLevel(logging.ERROR)
