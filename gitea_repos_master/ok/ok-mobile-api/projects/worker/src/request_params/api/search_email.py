from src.request_params.interfaces.search import SearchParams


class SearchEmailParams(SearchParams):
    def __init__(self, email, *args, **kwargs):
        super().__init__(credentials={"email": email}, *args, **kwargs)
