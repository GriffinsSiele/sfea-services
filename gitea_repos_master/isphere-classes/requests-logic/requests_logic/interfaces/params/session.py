import requests
from pydash import get
from requests import Session

from requests_logic.ja3_factory import JA3Factory
from requests_logic.tls_adapter import TLSAdapter


class SessionParams:
    _session = None

    def _get_session(self):
        return Session()

    @property
    def session(self):
        if not self._session:
            self._session = self._get_session()
        return self._session

    @session.setter
    def session(self, value=None):
        self._session = value


class SessionJA3Params(SessionParams):
    def __init__(self, ja3_options, *args, **kwargs):
        super().__init__(*args, **kwargs)

        self.options = ja3_options
        self.adapter = None

    def _get_session(self):
        ja3_server_url = get(self.options, "url")
        session, self.adapter = JA3Factory(ja3_server_url).create_session_by_options(
            self.options
        )
        return session


class SessionTLSParams(SessionParams):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

    def _get_session(self):
        session = requests.session()

        session.mount("http://", TLSAdapter())
        session.mount("https://", TLSAdapter())

        return session
