from urllib.parse import urlparse


class URLParams:
    def __init__(self, url=None, domain=None, path=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._domain = domain if domain else ""
        self._path = path if path else ""

        if url:
            parsed = urlparse(url)
            self._domain = parsed.scheme + "://" + parsed.netloc
            self._path = parsed.path

    @property
    def url(self):
        return self._domain + self._path

    @property
    def domain(self):
        return self._domain

    @domain.setter
    def domain(self, value=None):
        self._domain = value if value else ""

    @property
    def path(self):
        return self._path

    @path.setter
    def path(self, value=None):
        self._path = value if value else ""
