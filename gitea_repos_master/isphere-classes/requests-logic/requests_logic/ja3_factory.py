from pydash import get
from requests import Session
from requests_logic.ja3_adapter import JA3Adapter


class JA3Factory:
    def __init__(self, ja3_server_url):
        self.ja3_server_url = ja3_server_url

    def create_adapter_by_options(self, options):
        ja3_id = get(options, "id")
        if ja3_id:
            return self.create_adapter_by_id(ja3_id)

        user_agent = get(options, "user_agent")
        ja3 = get(options, "ja3")
        if ja3:
            return self.create_adapter_by_value(ja3, user_agent)

        return self.create_adapter_by_id()

    def create_base_adapter(self):
        adapter = JA3Adapter()
        adapter.set_proxy_server_url(self.ja3_server_url)
        return adapter

    def create_adapter_by_id(self, ja3_id=2):
        adapter = self.create_base_adapter()
        adapter.set_ja3_by_index(ja3_id)
        return adapter

    def create_adapter_by_value(self, ja3, user_agent):
        adapter = self.create_base_adapter()
        adapter.set_ja3(ja3, user_agent)
        return adapter

    def create_session(self, adapter=None):
        session = Session()
        adapter = self.create_adapter_by_id() if not adapter else adapter
        return self.set_session_adapter(session, adapter)

    def create_session_by_options(self, options):
        session = Session()
        adapter = self.create_adapter_by_options(options)
        return self.set_session_adapter(session, adapter)

    def set_session_adapter(self, session, adapter):
        session.mount("https://", adapter)
        session.mount("http://", adapter)

        return session, adapter
