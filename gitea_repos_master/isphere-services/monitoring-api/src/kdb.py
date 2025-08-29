from keydb import KeyDB


class KeyDBQueue:
    def __init__(self: str, host: str, password: str) -> None:
        self.db = KeyDB(host=host, password=password)

    def _set_service(self, service):
        self.service = service

    @property
    def queue_name(self):
        return f'{self.service}_queue'

    @property
    def reestr_name(self):
        return f'{self.service}_reestr'

    def get_len(self, service):
        self._set_service(service)
        queue_len = self.db.llen(self.queue_name)
        reestr_len = self.db.llen(self.reestr_name)
        hash_len = self.db.hlen(service)
        return {f'{self.queue_name}': queue_len, f'{self.reestr_name}': reestr_len, 'hash_len': hash_len}

    def get_keys(self):
        return self.db.keys('*[^queue]')
