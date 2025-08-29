import asyncio
import logging
from asyncio import Lock
from typing import Callable

from pydash import get


class SessionConcurrency:
    def __init__(
        self,
        session_getter: Callable,
        count=3,
        logger=logging,
    ):
        self.count = count + 1
        self.session_getter = session_getter
        self.logging = logger

        self.sessions = {k: {"locked": False, "session": None} for k in range(self.count)}

        self.mutex = Lock()

    def get_session_by_worker(self, worker):
        session = self.sessions[worker]
        return get(session, "session")

    async def get_session(self, with_locking=True):
        await self.mutex.acquire()

        response = (0, None)
        for worker, session_data in self.sessions.items():
            locked, data = session_data.get("locked"), session_data.get("session")
            if not locked and data:
                self.sessions[worker]["locked"] = bool(with_locking)
                response = (worker, data)
                break

        self.mutex.release()
        return response

    async def update_session(self, worker):
        if worker is None:
            return

        session = await self.session_getter()
        self.sessions[worker] = {"locked": False, "session": session}
        return (worker, session)

    async def update_many_sessions(self, workers):
        await asyncio.gather(*[self.update_session(w) for w in workers])

    async def init_sessions(self):
        await self.update_many_sessions([i for i in range(self.count)])
        self.logging.info(f"Current session state: {self.sessions}")
