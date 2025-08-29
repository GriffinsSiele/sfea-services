import json
import logging
import re

from isphere_exceptions.session import SessionLocked
from pydash import get, pick

from src.logic.misc.proxy import proxy_cache_manager
from src.logic.misc.version import Version2GISManager
from src.request_params.api.auth import Auth2GIS


class Authorizer2GIS:
    def __init__(self, auth_data=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.cookies = get(auth_data, "cookies", {})
        self.auth_query = get(auth_data, "auth_query", {})
        self.ja3_options = pick(auth_data, "ja3", "user_agent")
        self.proxy = get(auth_data, "proxy_id")
        self.captcha_block = get(auth_data, "captcha_block", False)

        self._auth_data = auth_data

    async def _prepare_proxy(self):
        self.proxy = (
            await proxy_cache_manager.get_proxy(
                proxy_id=self.proxy,
                fallback_query={"proxygroup": "5"},
                repeat=3,
                adapter="simple",
            )
            if self.proxy
            else None
        )
        logging.info(f"Using proxy: {self.proxy}")

    def is_authed_session(self):
        return self.auth_query

    async def set_auth(self):
        try:
            auth = Auth2GIS(proxy=self.proxy)
            response = await auth.request()
        except Exception as e:
            logging.error(f"Exception in auth: {e}")
            raise e

        settings_2gis = re.findall("JSON\.parse\('({.*}?)'\);\s+var i", response.text)
        try:
            payload = json.loads(get(settings_2gis, "0"))
        except Exception as e:
            logging.error(
                f"Exception in extracting session from auth: {e}. Response: {response.text}"
            )
            raise SessionLocked("Possible auth_data locked")

        search_user_hash = get(payload, "searchUserHash")
        session_id = get(payload, "sessionId")
        user_id = get(payload, "userId")

        Version2GISManager.update_by_payload(payload)

        if not search_user_hash or not session_id or not user_id:
            raise SessionLocked("Empty data in authorization response")

        self.auth_query = {
            "search_user_hash": search_user_hash,
            "stat[sid]": session_id,
            "stat[user]": user_id,
        }
        logging.info(f"Set auth query: {self.auth_query}")

        self.cookies = (
            dict(response.cookies)
            if response.cookies and not self.cookies
            else self.cookies
        )
        logging.info(f"Set cookies: {self.cookies}")

    def get_auth(self):
        return {
            "cookies": self.cookies,
            "auth_query": self.auth_query,
            "ja3": get(self.ja3_options, "ja3"),
            "user_agent": get(self.ja3_options, "user_agent"),
            "proxy_id": get(self.proxy, "extra_fields.id"),
            "captcha_block": self.captcha_block,
        }
