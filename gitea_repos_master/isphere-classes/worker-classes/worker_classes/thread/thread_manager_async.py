import asyncio
import logging
import time
from time import sleep
from typing import Any, Type, Dict

from livenessprobe_logic import HealthCheck
from pydash import get

from worker_classes.keydb.adapter import KeyDBAdapter
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.exception_handler import ExceptionHandler


class ThreadManager:
    SLEEP_FOR_NEXT_TASK_INTERVAL = 0.1
    COUNT_USE_FOR_RELOAD = 0

    def __init__(
        self,
        mongo,
        kdbq,
        exception_handler: ExceptionHandler,
        builder_xml: KeyDBBuilderXML,
        rsm=None,
    ):
        self.mongo = mongo
        self.kdbq = kdbq
        self.exception_handler = exception_handler
        self.builder_xml = builder_xml
        self.rsm = rsm

        self.count_reload = 0

    async def run(self, search_manager: Type[SearchManager], *args, **kwargs):
        logging.info("Worker started")
        try:
            return await self._run(search_manager, *args, **kwargs)
        except Exception as e:
            logging.fatal(e)
            exit(1)

    async def _run(self, search_manager: Type[SearchManager], *args, **kwargs):
        while self.is_alive():
            start_time = time.time()
            payload = await self.kdbq.check_queue()

            if not payload:
                sleep(ThreadManager.SLEEP_FOR_NEXT_TASK_INTERVAL)
                continue

            logging.info(f"LPOP {payload}")
            session = None
            if self.mongo:
                session = await self.mongo.get_session()
                logging.info(f"Using session: {session}")

                if not session and self.rsm:
                    is_ok, sleep_time = await self.rsm.solve_lack_sessions(self.mongo, 0)
                    if not is_ok:
                        logging.info("Task returned, wait for solving lack of sessions")
                        await self.kdbq.return_to_queue(payload)
                        sleep(sleep_time)
                        continue

            await self._process_payload(payload, session, search_manager, *args, **kwargs)
            end_time = time.time()
            logging.info(
                f"Total processing task time: {round(end_time - start_time, 2)} sec"
            )

    async def _process_payload(
        self,
        payload: Any,
        session: Dict,
        search_manager: Type[SearchManager],
        *args,
        **kwargs,
    ):
        api = None
        try:
            api = search_manager(auth_data=get(session, "session"), *args, **kwargs)
            response = await api.search(payload)
            response = KeyDBAdapter().to_key_db(response, self.builder_xml)

            tasks = [self.kdbq.set_answer(payload, KeyDBResponseBuilder.ok(response))]
            if self.mongo:
                tasks.append(self.mongo.session_success(session))
            await asyncio.gather(*tasks)
            self.count_reload += 1
            HealthCheck().checkpoint()
        except Exception as e:
            await self.exception_handler.call(
                e,
                mongo=self.mongo,
                kdbq=self.kdbq,
                session=session,
                payload=payload,
                api=api,
            )
        else:
            await self.exception_handler.normal(
                mongo=self.mongo,
                kdbq=self.kdbq,
                session=session,
                payload=payload,
                api=api,
            )

    def is_alive(self):
        if self.COUNT_USE_FOR_RELOAD == 0:
            return True

        return self.count_reload < self.COUNT_USE_FOR_RELOAD
