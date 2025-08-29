class HeadersParams:
    def __init__(self, headers=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._headers = headers

    @property
    def headers(self):
        return self._headers

    @headers.setter
    def headers(self, value=None):
        self._headers = value
