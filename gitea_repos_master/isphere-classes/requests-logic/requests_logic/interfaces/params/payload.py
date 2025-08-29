class PayloadParams:
    def __init__(self, payload=None, data=None, json=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._payload = payload or json
        self._payload_type = "json"

        if data:
            self._payload = data
            self._payload_type = "data"

    @property
    def payload(self):
        return self._payload

    @payload.setter
    def payload(self, value=None):
        self._payload = value
