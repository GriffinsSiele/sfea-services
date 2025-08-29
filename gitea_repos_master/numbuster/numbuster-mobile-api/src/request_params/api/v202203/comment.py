from src.request_params.interfaces.authed import AuthedParams


class CommentParams(AuthedParams):
    def __init__(self, phone_number: str, *args, **kwargs):
        super().__init__(
            path="/api/v202203/comment/list",
            query={
                "phone": phone_number,
                "offset": "0",
                "limit": "15",
                "limit_thread": "4",
                "order_by": "interesting",
            },
            *args,
            **kwargs,
        )
