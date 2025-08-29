import json
import logging

import websocket
from pydash import get


class Socket:
    def __init__(self, proxy=None):
        websocket.enableTrace(False)

        self.ws = websocket.WebSocket()
        self.ws.connect(
            "wss://api-messages-ws.ok.ru/websocket",
            timeout=12,
            **self.__proxy_cast(proxy),
        )

        self.counter_sequence = 0

    def ws(self):
        return self.ws

    def __proxy_cast(self, proxy):
        if not proxy:
            return {}
        return {
            "http_proxy_host": get(proxy, "extra_fields.server"),
            "http_proxy_port": get(proxy, "extra_fields.port"),
            "http_proxy_auth": (
                get(proxy, "extra_fields.login"),
                get(proxy, "extra_fields.password"),
            ),
        }

    def send(self, message):
        data = {**message, "seq": self.counter_sequence}
        self.ws.send(json.dumps(data))
        self.counter_sequence += 1

        response = self.receive()
        logging.debug(response)

        return response

    def receive(self):
        response = self.ws.recv()
        return json.loads(response)

    def close(self):
        self.ws.close()
