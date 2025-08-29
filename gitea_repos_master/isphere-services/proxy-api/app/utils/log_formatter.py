import logging


class NotEndLineMessageFormatter(logging.Formatter):
    def formatMessage(self, record: logging.LogRecord) -> str:
        return super().formatMessage(record).replace("\n", " ").replace(" " * 4, "")

    def formatException(self, ei) -> str:
        return super().formatException(ei).replace("\n", " ")
