class MethodParams:
    def __init__(self, method=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._method = method if method else "GET"

    @property
    def method(self):
        return self._method

    @method.setter
    def method(self, value=None):
        self._method = value if value else "GET"
