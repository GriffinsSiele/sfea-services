from typing import Dict, Optional

from mongo_client.client import MongoSessions as MongoDefault

from lib.src.config.app import ConfigApp


class MongoSessions(MongoDefault):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    async def get_activation_sessions(self, f):
        return await self.sessions.find_one(filter=f)

    async def get_session(
        self, count_use: int = 1, next_use_delay: Optional[int] = None
    ) -> Dict:
        next_use_delay_ = next_use_delay or 60 * 60 * ConfigApp.WAIT_NEXT_USE_INTERVAL
        return await super().get_session(count_use, next_use_delay_)
