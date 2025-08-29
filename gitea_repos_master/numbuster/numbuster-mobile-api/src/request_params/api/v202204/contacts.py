from src.request_params.interfaces.authed import AuthedParams


class ContactsParams(AuthedParams):
    def __init__(self, phone_number: str, *args, **kwargs):
        super().__init__(
            path="/api/v202204/contacts/list",
            query={
                "phone": phone_number,
            },
            *args,
            **kwargs,
        )
