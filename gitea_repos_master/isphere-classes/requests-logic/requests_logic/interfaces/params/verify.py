class VerifyParams:
    def __init__(self, verify=False, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._verify = verify

    @property
    def verify(self):
        return self._verify

    @verify.setter
    def verify(self, value=False):
        self._verify = value
