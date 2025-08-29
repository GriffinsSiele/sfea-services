import logging
from urllib.parse import parse_qs

from aiohttp import ClientHttpProxyError, ClientProxyConnectionError
from isphere_exceptions.proxy import ProxyBlocked
from isphere_exceptions.source import SourceDown
from isphere_exceptions.success import NoDataEvent
from isphere_exceptions.worker import UnknownError
from pydash import get, sort_by
from urllib3.exceptions import ProxyError
from worker_classes.logic.search_manager import SearchManager

from src.config.app import ConfigApp
from src.logic.adapters.response import ResponseAdapter
from src.logic.proxy.proxy import ProxyCacheManager, proxy_cache_manager
from src.request_params.api.create_chat import CreateChatParams
from src.socket_params.api.socket_manager import SocketManager


class SearchOKManager(SearchManager):
    def __init__(self, auth_data=None, proxy=False, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.use_proxy = proxy
        self.sm = None
        self.exception_down = None

    async def _search(self, payload, *args, **kwargs):
        if self.exception_down:
            raise self.exception_down

        payload = str(
            payload.get("phone", "") or payload.get("email", "")
            if isinstance(payload, dict)
            else payload
        )
        response = await self._search_payload(payload)
        self.sm.close()
        return sort_by(response, "user_name")

    async def _prepare(self):
        self.exception_down = None
        self.proxy = (
            await proxy_cache_manager.get_proxy(
                {"proxygroup": "1"},
                fallback_query={"proxygroup": "1"},
                repeat=3,
                adapter="simple",
            )
            if self.use_proxy
            else None
        )
        logging.info(f"Using proxy: {self.proxy}")

        try:
            await self.create_socket_manager()
        except SourceDown as e:
            self.exception_down = e

    async def create_socket_manager(self):
        if self.sm:
            self.sm.close()

        logging.info("New socket connection started")
        chat_id, chat_link = await self.create_chat()
        self.sm = await self.create_connection(chat_id, chat_link)
        return self.sm

    async def create_chat(self):
        try:
            cc = CreateChatParams(proxy=self.proxy)
            response = await cc.request()
        except (
            ProxyError,
            ConnectionError,
            ClientProxyConnectionError,
            ClientHttpProxyError,
            TimeoutError,
        ) as e:
            ProxyCacheManager.clear_cache()
            if "Cannot connect to host" in str(e):
                logging.warning(e)
                raise ProxyBlocked("Cannot connect to host")
            raise ProxyBlocked(e)
        except Exception as e:
            raise SourceDown(
                "На данный момент сервис ОК не позволяет получить доступ к чату поддержки"
            )

        r_text = response.text[:200].replace("\n", "")
        logging.info(f"Response: [{response.status_code}] {r_text}")

        chat_redirect_url = parse_qs(get(response.headers, "Location"))
        chat_link = get(chat_redirect_url, "st\.chatLink.0")

        if not chat_link:
            raise SourceDown("На данный момент чат с поддержкой недоступен")

        chat_id = -int(get(chat_redirect_url, "st\.chatId.0"))

        return chat_id, chat_link

    async def create_connection(self, chat_id, chat_link):
        sm = SocketManager(chat_link, chat_id, proxy=self.proxy)
        logging.info("Created socket")
        try:
            sm.init_chat_filling()
        except Exception as e:
            logging.warning(e)
        return sm

    async def _search_payload(self, payload, allowed_restart=True):
        logging.info("Start search after creating socket connection")
        output = []

        try:
            response = self.sm.start_search(payload)
        except Exception as e:
            logging.warning(f"Exception occurred due to search start: {type(e)} {e}")
            # Если произошла ошибка, то ничего не нашел, упал сокет, пробрасываем ошибку
            if not allowed_restart:
                raise UnknownError(f"Во время запросов websocket произошла ошибка: {e}")

            await self.create_socket_manager()
            return await self._search_payload(payload, allowed_restart=False)

        try:
            output.append(self.next_user_info(response, output, payload))
        except Exception as e:
            # Если во время разбора произошла ошибка, то все последующие поиски профиля бессмысленны
            self.sm.close()
            raise e

        for _ in range(ConfigApp.MAX_PROFILES_SEARCH - 1):
            try:
                response = self.sm.next_search()
            except Exception as e:
                # Если во время поисков "Сомневаюсь" возникли ошибки, игнорируем, может на след. итерации повезет
                logging.warning(f"Ошибка по время доп. поиска: {e}")
                continue

            try:
                output.append(self.next_user_info(response, output, payload))
            except NoDataEvent:
                logging.info("Stop search")
                # Если не найден еще профиль - остановка поиска
                break
            except Exception as e:
                # Если еще какая-то ошибка разбора - игнорируем, пытаемся еще
                logging.error(f"Ошибка по время разбора доп. поиска: {e}")
                continue

        return output

    def next_user_info(self, response, output, payload):
        data = ResponseAdapter.cast(response, payload)
        logging.info(f"Extending output with new user: {data}")
        return data
