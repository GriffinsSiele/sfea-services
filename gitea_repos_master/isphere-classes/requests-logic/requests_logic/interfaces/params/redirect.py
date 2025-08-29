class RedirectParams:
    def __init__(self, redirect=True, allow_redirects=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._redirect = allow_redirects or redirect

    @property
    def redirect(self):
        return self._redirect

    @redirect.setter
    def redirect(self, value=True):
        self._redirect = value
