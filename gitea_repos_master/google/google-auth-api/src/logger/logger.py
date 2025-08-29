import logging

from worker_classes.logger import Logger as L


class Logger(L):
    def create(self):
        super().create()
        self._set_selenium()

    @staticmethod
    def _set_selenium():
        logging.getLogger("undetected_chromedriver").setLevel(logging.WARNING)
