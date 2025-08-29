import logging
import random
from asyncio import sleep

from telethon import functions

from src.logic.generator.dialog import DialogGenerator
from src.logic.proxy.proxy import ProxyCacheManager
from src.request_params.api.telegram_auth import TelegramAuth


class DialogManager:
    def __init__(self, session_1, session_2):
        self.session_1 = session_1
        self.session_2 = session_2

    async def get_proxy(self, proxy_id):
        return await ProxyCacheManager.get_proxy(
            proxy_id, fallback_query={"proxygroup": "5"}
        )

    async def __prepare(self):
        self.proxy_1 = await self.get_proxy(self.session_1["proxy_id"])
        self.proxy_2 = await self.get_proxy(self.session_2["proxy_id"])
        del self.proxy_1["id"]
        del self.proxy_2["id"]
        logging.info(f"Using proxy_1: {self.proxy_1}")
        logging.info(f"Using proxy_2: {self.proxy_1}")

    async def get_user(self, session, proxy):
        session_creator = TelegramAuth(
            auth_key=session["auth_key"],
            api_id=session["api_id"],
            api_hash=session["api_hash"],
            proxy=proxy,
        )

        client = await session_creator.request()
        await client(functions.account.UpdateStatusRequest(offline=False))

        me = await client.get_me()
        logging.info(f"User: {me}")
        return client, me

    async def start(self):
        await self.__prepare()
        if not self.session_1 or not self.session_2:
            return

        client_1, me_1 = await self.get_user(self.session_1, self.proxy_1)
        await sleep(random.randint(1, 5))
        client_2, me_2 = await self.get_user(self.session_2, self.proxy_2)

        try:
            r = await client_1.send_message(me_2, DialogGenerator().generate_message())
            logging.info(f"Message 1: {r}")
            await sleep(random.randint(1, 5))

            r = await client_2.send_message(me_1, DialogGenerator().generate_message())
            logging.info(f"Message 2: {r}")

            await sleep(random.randint(1, 5))
            await client_1(functions.account.UpdateStatusRequest(offline=True))
            await sleep(random.randint(1, 5))
            await client_2(functions.account.UpdateStatusRequest(offline=True))
        except Exception as e:
            logging.error(e)
        finally:
            await client_1.disconnect()
            await client_2.disconnect()
