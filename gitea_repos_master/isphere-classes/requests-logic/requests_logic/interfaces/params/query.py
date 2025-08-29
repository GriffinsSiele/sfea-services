class QueryParams:
    def __init__(self, query=None, params=None, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._query = query or params

    @property
    def query(self):
        return self._query

    @query.setter
    def query(self, value=None):
        self._query = value
