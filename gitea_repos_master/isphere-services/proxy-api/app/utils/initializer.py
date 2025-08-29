import logging

from app.models.base import Base
from app.settings import LOG_FORMAT, LOG_LEVEL, MODE
from app.tasks.schedulers import TaskRunner
from app.utils.database import DatabaseManager
from app.utils.log_formatter import NotEndLineMessageFormatter


class Initializer:
    @staticmethod
    async def initialize():
        Initializer.set_loggers()
        await Initializer.create_db_tables()
        if MODE == "prod":
            Initializer.run_tasks()

    @staticmethod
    async def create_db_tables():
        async with DatabaseManager.with_async_connection() as connection:
            await connection.run_sync(Base.metadata.create_all)

    @staticmethod
    def run_tasks():
        TaskRunner.run_thread()

    @staticmethod
    def set_loggers():
        logging.basicConfig(format=LOG_FORMAT)

        def set_formatter(_loggers: list[logging.Logger], formatter: logging.Formatter):
            for _logger in _loggers:
                for handler in _logger.handlers:
                    handler.setFormatter(formatter)

        # GET ALL THE LOGGERS AND SET A SPECIFIC FORMATTER
        sql_logger = logging.getLogger("sqlalchemy.engine.Engine")
        sql_logger.setLevel(LOG_LEVEL)
        loggers = [
            logging.getLogger("root"),
            logging.getLogger("uvicorn"),
            logging.getLogger("uvicorn.access"),
            logging.getLogger("uvicorn.error"),
            logging.getLogger("uvicorn.warning"),
            logging.getLogger("uvicorn.exception"),
            logging.getLogger("uvicorn.debug"),
            sql_logger,
        ]
        not_end_line_fmt = NotEndLineMessageFormatter(LOG_FORMAT)
        set_formatter(loggers, not_end_line_fmt)
