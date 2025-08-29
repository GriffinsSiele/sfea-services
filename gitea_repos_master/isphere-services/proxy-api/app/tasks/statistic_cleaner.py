import logging

from app.managers.proxy_usage import ProxyUsageManager
from app.settings import LOGGER_NAME
from app.utils.database import DatabaseManager


logger = logging.getLogger(LOGGER_NAME)


class StatisticCleaner:
    @staticmethod
    async def clean_statistic():
        async with DatabaseManager.with_async_session() as session:
            pu_manager = ProxyUsageManager(session)
            await pu_manager.update(
                where_conditions=[],
                count_use=0,
                last_use=None,
                count_success=0,
                last_success=None,
            )
            await pu_manager.session.commit()
            logger.info("Statistics update completed.")
