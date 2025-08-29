class ProxyParams:
    def __init__(self, proxy=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._proxy = proxy

    @property
    def proxy(self):
        return self._proxy

    @proxy.setter
    def proxy(self, value=None):
        self._proxy = value
