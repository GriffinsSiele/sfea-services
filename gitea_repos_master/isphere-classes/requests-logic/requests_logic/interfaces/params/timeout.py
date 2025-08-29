class TimeoutParams:
    def __init__(self, timeout=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._timeout = timeout

    @property
    def timeout(self):
        return self._timeout

    @timeout.setter
    def timeout(self, value=None):
        self._timeout = value
