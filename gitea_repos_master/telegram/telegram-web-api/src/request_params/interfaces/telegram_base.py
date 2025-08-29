class TelegramBaseParams:
    def __init__(self, client, function, *args, **kwargs):
        self.client = client
        self.function = function
        self.function_args = args
        self.function_kwargs = kwargs

    async def request(self):
        if isinstance(self.function, str):
            coroutine = getattr(self.client, self.function)(
                *self.function_args, **self.function_kwargs
            )
        else:
            coroutine = self.client(
                self.function(*self.function_args, **self.function_kwargs)
            )

        return await coroutine
