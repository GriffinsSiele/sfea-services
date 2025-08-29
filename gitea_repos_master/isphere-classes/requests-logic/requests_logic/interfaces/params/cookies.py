class CookiesParams:
    def __init__(self, cookies=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._cookies = cookies

    @property
    def cookies(self):
        return self._cookies

    @cookies.setter
    def cookies(self, value=None):
        self._cookies = value
