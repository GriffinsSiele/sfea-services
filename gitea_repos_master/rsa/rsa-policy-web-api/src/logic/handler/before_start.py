import logging

import pytesseract
from worker_classes.sentry.sentry import Sentry, sentry_remove_context

from src.config.configuration import RecognizerConfig, SentryConfig

TESSERACT_PATH = RecognizerConfig.tesseract_path
SENTRY_URL = SentryConfig.url
SENTRY_MODE = SentryConfig.mode


def before_start() -> None:

    Sentry(SENTRY_URL, SENTRY_MODE, custom_log_formatter=sentry_remove_context).create()
    logging.basicConfig(
        level=logging.INFO,
        format=f"%(asctime)s - [%(levelname)s] - (%(filename)s).%(funcName)s(%(lineno)s) - %(message)s",
    )
    # Путь до Teseract, без указания переменной среды
    pytesseract.pytesseract.tesseract_cmd = TESSERACT_PATH
