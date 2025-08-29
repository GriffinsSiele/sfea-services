import asyncio
import logging
from typing import Any, Type

from livenessprobe_logic import HealthCheck
from pydash import get

from worker_classes.keydb.adapter import KeyDBAdapter
from worker_classes.keydb.builder_xml import KeyDBBuilderXML
from worker_classes.keydb.response_builder import KeyDBResponseBuilder
from worker_classes.logger import Logger
from worker_classes.logic.search_manager import SearchManager
from worker_classes.thread.exception_handler import ExceptionHandler
from worker_classes.thread.session_concurrent import SessionConcurrency


class ThreadManagerRabbitMQ:
    COUNT_USE_FOR_RELOAD = 0

    def __init__(
        self,
        mongo,
        kdbq,
        rabbitmq: "RabbitMQConsumer",
        exception_handler: ExceptionHandler,
        builder_xml: KeyDBBuilderXML,
        rsm=None,
    ):
        self.mongo = mongo
        self.kdbq = kdbq
        self.rabbitmq = rabbitmq
        self.exception_handler = exception_handler
        self.builder_xml = builder_xml
        self.rsm = rsm

        self.count_reload = 0

        self.logging = logging

        self.session_concurrent = SessionConcurrency(
            mongo.get_session, self.rabbitmq.consumer_count, logger=self.logging
        )

    async def run(self, search_manager: Type[SearchManager], *args, **kwargs):
        try:
            return await self._run(search_manager, *args, **kwargs)
        except Exception as e:
            logging.fatal(e)
            exit(1)

    async def _run(self, search_manager, *args, **kwargs):
        self.search_manager = search_manager
        self.args = args
        self.kwargs = kwargs

        self.rabbitmq.on_message_callback = self.on_message_callback
        self.rabbitmq.before_message_callback = self.before_message_callback

        await self.session_concurrent.init_sessions()
        await self.before_start()
        await self.rabbitmq.run()

    async def on_message_callback(self, message):
        return await self._process_payload(
            message.body, self.search_manager, *self.args, **self.kwargs
        )

    async def before_message_callback(self, message):
        self.logging.info(f"Check is exists task in keydb: {message.body}")
        is_existed = await self.kdbq.check_exists_with_update_ttl(message.body)
        self.logging.info(f"Cache existed for {message.body}: {is_existed}")
        return not is_existed

    async def before_start(self):
        worker_id, session = await self.session_concurrent.get_session(with_locking=False)
        if not session:
            await self._solve_lack_sessions(0)

    async def after_loop(self, worker_id):
        self.logging.info(f"Getting new session for worker {worker_id}")
        _, session = await self.session_concurrent.update_session(worker_id)
        self.logging.info(f"Session: {session}")

        if not session:
            await self._solve_lack_sessions(worker_id)

    async def _solve_lack_sessions(self, worker_id):
        while True:
            is_ok, sleep_time = await self.rsm.solve_lack_sessions(self.mongo, worker_id)
            if is_ok:
                await self.session_concurrent.init_sessions()
                break

            if not self.session_concurrent.get_session_by_worker(worker_id):
                self.logging.info("Not solved lack of sessions. Wait...")
                await asyncio.sleep(sleep_time)

    async def _process_payload(
        self, payload: Any, search_manager: Type[SearchManager], *args, **kwargs
    ):
        worker, api = None, None
        try:
            worker, session = await self.session_concurrent.get_session()
            logging = Logger().create_logger_worker(worker)
            logging.info(f"Current worker: {worker}")
            logging.info(f"Using session: {session}")
            api = search_manager(
                auth_data=get(session, "session"), logger=logging, *args, **kwargs
            )

            response = await api.search(payload)
            response = KeyDBAdapter().to_key_db(response, self.builder_xml)

            await self.kdbq.set_answer(payload, KeyDBResponseBuilder.ok(response))
            logging.info("Response in keydb")

            await self.mongo.session_success(session)
            self.count_reload += 1
            HealthCheck().checkpoint()
        except Exception as e:
            return_params = await self.exception_handler.call(
                e,
                logger=logging,
                mongo=self.mongo,
                kdbq=self.kdbq,
                session=session,
                payload=payload,
                api=api,
            )
        else:
            return_params = await self.exception_handler.normal(
                logger=logging,
                mongo=self.mongo,
                kdbq=self.kdbq,
                session=session,
                payload=payload,
                api=api,
            )

        await self.after_loop(worker)
        return return_params

    @property
    def is_alive(self):
        if self.COUNT_USE_FOR_RELOAD == 0:
            return True

        return self.count_reload < self.COUNT_USE_FOR_RELOAD
