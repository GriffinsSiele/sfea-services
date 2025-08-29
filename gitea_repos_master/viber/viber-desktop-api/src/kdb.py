from queue_logic.keydb import KeyDBQueue
from keydb import ConnectionPool, KeyDB
import time
from queue_logic.exceptions import (
    ErrorNoReturnToQueue,
    ErrorReturnToQueue,
    ErrorStopNoReturnToQueue,
    ErrorStopReturnToQueue,
)
from livenessprobe_logic import HelthCheck


class NewKeyDBQueue(KeyDBQueue):
    def __init__(self, url, service, delay_keydb=0.1):
        pool = ConnectionPool.from_url(
            url=url,
            health_check_interval=10,
            socket_connect_timeout=1,
            socket_timeout=5,
            socket_keepalive=True,
        )
        self.db = KeyDB(connection_pool=pool)
        self.service = service
        self.delay_keydb = delay_keydb
        self.timeout = 86400

    def run_loop(self, main_func):
        # основной процесс лупа принимает на вход функцию обработчик
        while True:
            payload = self.check_queue()
            if payload:
                try:
                    res = main_func(payload)
                    self.set_answer(payload, res)
                    HelthCheck().checkpoint()
                except KeyboardInterrupt:
                    self.return_to_queue(payload)
                    exit(0)
                except ErrorStopReturnToQueue:
                    self.return_to_queue(payload)
                    break
                except ErrorStopNoReturnToQueue:
                    res = {
                        "status": "Error",
                        "code": e.code,
                        "message": e.message,
                        "records": [],
                    }
                    self.set_answer(payload, res)
                    break
                except ErrorReturnToQueue:
                    self.return_to_queue(payload)
                except ErrorNoReturnToQueue as e:
                    res = {
                        "status": "Error",
                        "code": e.code,
                        "message": e.message,
                        "records": [],
                    }
                    self.set_answer(payload, res)
            time.sleep(self.delay_keydb)
